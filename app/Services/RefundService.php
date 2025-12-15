<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RefundPolicy;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RefundService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Calculate refund amount based on policies
     */
    public function calculateRefund(
        Reservation $reservation,
        ?Carbon $cancellationDate = null,
        ?string $cancellationReason = null
    ): array {
        $cancellationDate = $cancellationDate ?? Carbon::now();
        $propertyId = $reservation->property_id;
        $checkIn = Carbon::parse($reservation->check_in);
        $bookingDate = Carbon::parse($reservation->created_at);
        
        $daysBeforeCheckin = $cancellationDate->diffInDays($checkIn, false);
        $daysAfterBooking = $cancellationDate->diffInDays($bookingDate, false);

        // Get applicable refund policies
        $policies = RefundPolicy::where('property_id', $propertyId)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->orderBy('is_default', 'desc')
            ->get();

        $applicablePolicy = null;

        foreach ($policies as $policy) {
            if ($this->policyApplies($policy, $reservation, $daysBeforeCheckin, $daysAfterBooking, $cancellationReason)) {
                $applicablePolicy = $policy;
                break;
            }
        }

        // If no specific policy applies, check for default
        if (!$applicablePolicy) {
            $applicablePolicy = RefundPolicy::where('property_id', $propertyId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (!$applicablePolicy) {
            // No refund if no policy applies
            return [
                'eligible' => false,
                'refund_amount' => 0,
                'refund_percentage' => 0,
                'policy' => null,
                'message' => 'No refund policy applicable',
            ];
        }

        $totalPaid = $reservation->paid_amount ?? $reservation->total_amount;
        $refundAmount = $this->calculateRefundAmount($applicablePolicy, $totalPaid);

        return [
            'eligible' => true,
            'refund_amount' => round($refundAmount, 2),
            'refund_percentage' => $applicablePolicy->refund_percentage ?? 0,
            'policy' => [
                'id' => $applicablePolicy->id,
                'name' => $applicablePolicy->name,
                'type' => $applicablePolicy->refund_type,
            ],
            'total_paid' => $totalPaid,
            'message' => "Refund eligible under policy: {$applicablePolicy->name}",
        ];
    }

    /**
     * Process refund for a reservation
     */
    public function processRefund(
        Reservation $reservation,
        ?float $amount = null,
        ?string $reason = null,
        ?Carbon $cancellationDate = null,
        bool $automatic = false
    ): array {
        try {
            // Calculate refund amount if not provided
            if ($amount === null) {
                $refundCalculation = $this->calculateRefund($reservation, $cancellationDate, $reason);
                
                if (!$refundCalculation['eligible']) {
                    return [
                        'success' => false,
                        'message' => $refundCalculation['message'],
                        'refund_calculation' => $refundCalculation,
                    ];
                }
                
                $amount = $refundCalculation['refund_amount'];
                $policyId = $refundCalculation['policy']['id'] ?? null;
            } else {
                $policyId = null;
            }

            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Refund amount must be greater than 0',
                ];
            }

            // Get payments for this reservation
            $payments = Payment::where('reservation_id', $reservation->id)
                ->where('status', Payment::STATUS_COMPLETED)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($payments->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No completed payments found for this reservation',
                ];
            }

            $remainingRefund = $amount;
            $processedRefunds = [];
            $totalRefunded = 0;

            foreach ($payments as $payment) {
                if ($remainingRefund <= 0) {
                    break;
                }

                $refundForThisPayment = min($remainingRefund, $payment->amount);
                
                $refundResult = $this->paymentService->refundPayment(
                    payment: $payment,
                    amount: $refundForThisPayment,
                    reason: $reason ?? 'reservation_cancellation'
                );

                if ($refundResult['success']) {
                    $processedRefunds[] = $refundResult['payment'];
                    $totalRefunded += $refundForThisPayment;
                    $remainingRefund -= $refundForThisPayment;
                } else {
                    Log::warning('Partial refund failed', [
                        'payment_id' => $payment->id,
                        'error' => $refundResult['error'] ?? 'Unknown error',
                    ]);
                }
            }

            // Update reservation status if fully refunded
            if ($totalRefunded >= $reservation->paid_amount) {
                $reservation->payment_status = Reservation::PAYMENT_REFUNDED;
            }

            // Update reservation cancellation
            if ($reservation->status !== Reservation::STATUS_CANCELLED) {
                $reservation->status = Reservation::STATUS_CANCELLED;
                $reservation->cancelled_at = $cancellationDate ?? Carbon::now();
            }

            $reservation->save();

            return [
                'success' => true,
                'total_refunded' => round($totalRefunded, 2),
                'requested_amount' => $amount,
                'processed_refunds' => $processedRefunds,
                'policy_id' => $policyId,
                'automatic' => $automatic,
                'message' => 'Refund processed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while processing the refund.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Check if a policy applies to the reservation
     */
    protected function policyApplies(
        RefundPolicy $policy,
        Reservation $reservation,
        int $daysBeforeCheckin,
        int $daysAfterBooking,
        ?string $cancellationReason = null
    ): bool {
        // Check days before check-in
        if ($policy->days_before_checkin !== null && $daysBeforeCheckin < $policy->days_before_checkin) {
            return false;
        }

        // Check days after booking
        if ($policy->days_after_booking !== null && $daysAfterBooking > $policy->days_after_booking) {
            return false;
        }

        // Check minimum nights
        if ($policy->minimum_nights !== null && $reservation->nights < $policy->minimum_nights) {
            return false;
        }

        // Check cancellation reason if required
        if ($policy->requires_cancellation_reason && $cancellationReason) {
            $allowedReasons = $policy->allowed_cancellation_reasons ?? [];
            if (!empty($allowedReasons) && !in_array($cancellationReason, $allowedReasons)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate refund amount based on policy
     */
    protected function calculateRefundAmount(RefundPolicy $policy, float $totalPaid): float
    {
        switch ($policy->refund_type) {
            case 'full':
                return $totalPaid;

            case 'fixed_amount':
                return min($policy->fixed_amount ?? 0, $totalPaid);

            case 'percentage':
            default:
                $percentage = $policy->refund_percentage ?? 0;
                return ($totalPaid * $percentage) / 100;
        }
    }

    /**
     * Get refund history for a reservation
     */
    public function getRefundHistory(Reservation $reservation): array
    {
        $payments = Payment::where('reservation_id', $reservation->id)
            ->where('status', Payment::STATUS_REFUNDED)
            ->orderBy('refunded_at', 'desc')
            ->get();

        return $payments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'amount' => $payment->refund_amount,
                'reason' => $payment->refund_reason,
                'refunded_at' => $payment->refunded_at,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
            ];
        })->toArray();
    }
}


