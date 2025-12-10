<?php

namespace App\Services;

use App\Models\CheckOut;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CheckOutService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected TaxService $taxService
    ) {
    }

    /**
     * Process check-out for a reservation
     */
    public function processCheckOut(
        Reservation $reservation,
        ?float $additionalCharges = 0,
        ?float $damages = 0,
        ?array $incidentals = [],
        ?array $roomCondition = null,
        ?array $keyReturn = null,
        ?string $departureNotes = null
    ): array {
        try {
            // Verify reservation is checked in
            if ($reservation->status !== Reservation::STATUS_CHECKED_IN) {
                return [
                    'success' => false,
                    'message' => 'Reservation must be checked in before checkout',
                ];
            }

            // Calculate final amount
            $finalAmount = $this->calculateFinalAmount($reservation, $additionalCharges, $damages);

            // Generate invoice
            $invoice = $this->generateInvoice(
                reservation: $reservation,
                additionalCharges: $additionalCharges,
                damages: $damages,
                incidentals: $incidentals
            );

            // Create check-out record
            $checkOut = CheckOut::create([
                'reservation_id' => $reservation->id,
                'property_id' => $reservation->property_id,
                'room_id' => $reservation->room_id,
                'invoice_id' => $invoice->id,
                'checked_out_by' => auth()->check() ? auth()->id() : null,
                'expected_check_out_at' => Carbon::parse($reservation->check_out)->setTime(11, 0), // Default checkout time
                'actual_check_out_at' => now(),
                'guest_count' => ($reservation->adult_count ?? 0) + ($reservation->child_count ?? 0),
                'departure_notes' => $departureNotes,
                'room_condition' => $roomCondition,
                'key_return' => $keyReturn,
                'incidentals' => $incidentals,
                'additional_charges' => $additionalCharges,
                'damages' => $damages,
                'final_amount' => $finalAmount,
                'payment_status' => $reservation->balance_due > 0 ? CheckOut::PAYMENT_PENDING : CheckOut::PAYMENT_PAID,
                'status' => CheckOut::STATUS_COMPLETED,
            ]);

            // Update reservation status
            $reservation->status = Reservation::STATUS_CHECKED_OUT;
            $reservation->checked_out_at = now();
            $reservation->total_amount = $finalAmount;
            $reservation->balance_due = max(0, $finalAmount - ($reservation->paid_amount ?? 0));
            
            if ($reservation->balance_due <= 0) {
                $reservation->payment_status = Reservation::PAYMENT_PAID;
            }
            
            $reservation->save();

            return [
                'success' => true,
                'check_out' => $checkOut->load(['invoice', 'reservation']),
                'invoice' => $invoice,
                'final_amount' => $finalAmount,
                'balance_due' => $reservation->balance_due,
                'message' => 'Check-out processed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Check-out processing error', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while processing check-out.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Generate invoice for check-out
     */
    protected function generateInvoice(
        Reservation $reservation,
        float $additionalCharges = 0,
        float $damages = 0,
        array $incidentals = []
    ): Invoice {
        $subtotal = $reservation->total_amount ?? 0;
        $subtotal += $additionalCharges + $damages;

        // Add incidentals
        foreach ($incidentals as $incidental) {
            $subtotal += $incidental['amount'] ?? 0;
        }

        // Calculate taxes on final amount
        $taxCalculation = $this->taxService->calculateTaxes(
            propertyId: $reservation->property_id,
            subtotal: $subtotal,
            date: Carbon::now(),
            roomTypeId: $reservation->room_type_id
        );

        // Build line items
        $lineItems = [
            [
                'description' => 'Room charges',
                'quantity' => $reservation->nights,
                'unit_price' => ($reservation->total_amount ?? 0) / max(1, $reservation->nights),
                'total' => $reservation->total_amount ?? 0,
            ],
        ];

        if ($additionalCharges > 0) {
            $lineItems[] = [
                'description' => 'Additional charges',
                'quantity' => 1,
                'unit_price' => $additionalCharges,
                'total' => $additionalCharges,
            ];
        }

        if ($damages > 0) {
            $lineItems[] = [
                'description' => 'Room damages',
                'quantity' => 1,
                'unit_price' => $damages,
                'total' => $damages,
            ];
        }

        foreach ($incidentals as $incidental) {
            $lineItems[] = [
                'description' => $incidental['description'] ?? 'Incidental charge',
                'quantity' => $incidental['quantity'] ?? 1,
                'unit_price' => $incidental['amount'] ?? 0,
                'total' => ($incidental['quantity'] ?? 1) * ($incidental['amount'] ?? 0),
            ];
        }

        $invoice = Invoice::create([
            'reservation_id' => $reservation->id,
            'property_id' => $reservation->property_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_SENT,
            'subtotal' => $subtotal,
            'tax_amount' => $taxCalculation['total_tax'],
            'total_amount' => $taxCalculation['grand_total'],
            'paid_amount' => $reservation->paid_amount ?? 0,
            'balance_due' => max(0, $taxCalculation['grand_total'] - ($reservation->paid_amount ?? 0)),
            'currency' => $reservation->currency ?? 'GBP',
            'line_items' => $lineItems,
            'tax_breakdown' => $taxCalculation['tax_breakdown'],
            'payment_terms' => 'Due on check-out',
        ]);

        return $invoice;
    }

    /**
     * Calculate final amount for check-out
     */
    protected function calculateFinalAmount(
        Reservation $reservation,
        float $additionalCharges = 0,
        float $damages = 0
    ): float {
        $baseAmount = $reservation->total_amount ?? 0;
        return $baseAmount + $additionalCharges + $damages;
    }

    /**
     * Collect payment during check-out
     */
    public function collectPayment(
        CheckOut $checkOut,
        float $amount,
        string $paymentMethod,
        ?string $paymentIntentId = null
    ): array {
        try {
            $reservation = $checkOut->reservation;
            $invoice = $checkOut->invoice;

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'No invoice found for this check-out',
                ];
            }

            // Process payment with invoice reference
            $paymentResult = $this->paymentService->processPayment(
                reservation: $reservation,
                amount: $amount,
                paymentMethod: $paymentMethod,
                paymentType: Payment::TYPE_FULL,
                paymentIntentId: $paymentIntentId,
                metadata: ['invoice_id' => $invoice->id]
            );

            // Update payment with invoice_id if not set
            if ($paymentResult['success'] && isset($paymentResult['payment']) && !$paymentResult['payment']->invoice_id) {
                $paymentResult['payment']->update(['invoice_id' => $invoice->id]);
            }

            if (!$paymentResult['success']) {
                return $paymentResult;
            }

            // Update invoice
            $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $amount;
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->paid_amount);
            
            if ($invoice->balance_due <= 0) {
                $invoice->status = Invoice::STATUS_PAID;
            }
            
            $invoice->save();

            // Update check-out payment status
            $checkOut->payment_status = $invoice->balance_due <= 0 
                ? CheckOut::PAYMENT_PAID 
                : CheckOut::PAYMENT_PARTIAL;
            $checkOut->payment_method = $paymentMethod;
            $checkOut->save();

            // Update reservation
            $reservation->paid_amount = ($reservation->paid_amount ?? 0) + $amount;
            $reservation->balance_due = max(0, $reservation->total_amount - $reservation->paid_amount);
            
            if ($reservation->balance_due <= 0) {
                $reservation->payment_status = Reservation::PAYMENT_PAID;
            }
            
            $reservation->save();

            return [
                'success' => true,
                'payment' => $paymentResult['payment'] ?? null,
                'invoice' => $invoice->fresh(),
                'check_out' => $checkOut->fresh(),
                'balance_due' => $invoice->balance_due,
                'message' => 'Payment collected successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Payment collection error during check-out', [
                'check_out_id' => $checkOut->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while collecting payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Get check-out details
     */
    public function getCheckOutDetails(Reservation $reservation): ?CheckOut
    {
        return CheckOut::where('reservation_id', $reservation->id)
            ->with(['invoice', 'room', 'property'])
            ->first();
    }
}

