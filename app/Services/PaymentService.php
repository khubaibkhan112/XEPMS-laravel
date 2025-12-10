<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class PaymentService
{
    public function __construct()
    {
        $stripeKey = config('services.stripe.secret');
        if ($stripeKey) {
            Stripe::setApiKey($stripeKey);
        }
    }

    /**
     * Create a payment intent for a reservation
     */
    public function createPaymentIntent(
        Reservation $reservation,
        float $amount,
        string $currency = 'GBP',
        array $metadata = []
    ): array {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'metadata' => array_merge([
                    'reservation_id' => $reservation->id,
                    'property_id' => $reservation->property_id,
                    'guest_name' => $reservation->guest_first_name . ' ' . $reservation->guest_last_name,
                ], $metadata),
                'description' => "Payment for Reservation #{$reservation->id}",
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Payment intent creation error', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while creating the payment intent.',
            ];
        }
    }

    /**
     * Confirm a payment intent
     */
    public function confirmPayment(
        string $paymentIntentId,
        Reservation $reservation,
        ?float $amount = null,
        string $paymentMethod = Payment::METHOD_STRIPE
    ): array {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Payment already succeeded
                $amount = $amount ?? ($paymentIntent->amount / 100);
                
                $payment = $this->createPaymentRecord(
                    reservation: $reservation,
                    amount: $amount,
                    currency: strtoupper($paymentIntent->currency),
                    paymentMethod: $paymentMethod,
                    gatewayTransactionId: $paymentIntent->id,
                    gatewayPaymentIntentId: $paymentIntent->id,
                    gatewayChargeId: $paymentIntent->latest_charge ?? null,
                    status: Payment::STATUS_COMPLETED,
                    gatewayResponse: $paymentIntent->toArray()
                );

                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Payment confirmed successfully',
                ];
            }

            return [
                'success' => false,
                'error' => "Payment intent status is: {$paymentIntent->status}",
            ];
        } catch (ApiErrorException $e) {
            Log::error('Payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Payment confirmation error', [
                'payment_intent_id' => $paymentIntentId,
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while confirming the payment.',
            ];
        }
    }

    /**
     * Process a payment
     */
    public function processPayment(
        Reservation $reservation,
        float $amount,
        string $paymentMethod = Payment::METHOD_STRIPE,
        string $paymentType = Payment::TYPE_FULL,
        ?string $paymentIntentId = null,
        array $metadata = []
    ): array {
        try {
            // Create payment record
            $invoiceId = $metadata['invoice_id'] ?? null;
            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'property_id' => $reservation->property_id,
                'invoice_id' => $invoiceId,
                'payment_method' => $paymentMethod,
                'payment_type' => $paymentType,
                'amount' => $amount,
                'currency' => $reservation->currency ?? 'GBP',
                'status' => Payment::STATUS_PROCESSING,
                'gateway' => $paymentMethod === Payment::METHOD_STRIPE ? 'stripe' : null,
                'gateway_payment_intent_id' => $paymentIntentId,
                'metadata' => $metadata,
                'description' => $invoiceId ? "Payment for Invoice" : "Payment for Reservation #{$reservation->id}",
            ]);

            // For non-Stripe methods, mark as completed immediately
            if ($paymentMethod !== Payment::METHOD_STRIPE) {
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);

                $this->updateReservationPayment($reservation, $amount);

                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Payment processed successfully',
                ];
            }

            // For Stripe, payment intent should already be confirmed
            if ($paymentIntentId) {
                $result = $this->confirmPayment($paymentIntentId, $reservation, $amount, $paymentMethod);
                
                if ($result['success']) {
                    return $result;
                }
            }

            return [
                'success' => false,
                'error' => 'Payment processing failed',
                'payment' => $payment,
            ];
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while processing the payment.',
            ];
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(
        Payment $payment,
        ?float $amount = null,
        string $reason = 'requested_by_customer'
    ): array {
        try {
            $refundAmount = $amount ?? $payment->amount;

            if ($payment->gateway === 'stripe' && $payment->gateway_charge_id) {
                $refund = Refund::create([
                    'charge' => $payment->gateway_charge_id,
                    'amount' => (int)($refundAmount * 100), // Convert to cents
                    'reason' => $reason,
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'reservation_id' => $payment->reservation_id,
                    ],
                ]);

                $payment->update([
                    'status' => Payment::STATUS_REFUNDED,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refunded_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['refund' => $refund->toArray()]
                    ),
                ]);

                // Update reservation payment status
                $reservation = $payment->reservation;
                $reservation->paid_amount = max(0, $reservation->paid_amount - $refundAmount);
                $reservation->balance_due = $reservation->total_amount - $reservation->paid_amount;
                $reservation->save();

                return [
                    'success' => true,
                    'payment' => $payment->fresh(),
                    'refund_id' => $refund->id,
                    'message' => 'Refund processed successfully',
                ];
            }

            // Manual refund for non-Stripe payments
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);

            $reservation = $payment->reservation;
            $reservation->paid_amount = max(0, $reservation->paid_amount - $refundAmount);
            $reservation->balance_due = $reservation->total_amount - $reservation->paid_amount;
            $reservation->save();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'message' => 'Refund processed successfully',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Refund processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while processing the refund.',
            ];
        }
    }

    /**
     * Create a payment record
     */
    protected function createPaymentRecord(
        Reservation $reservation,
        float $amount,
        string $currency,
        string $paymentMethod,
        ?string $gatewayTransactionId = null,
        ?string $gatewayPaymentIntentId = null,
        ?string $gatewayChargeId = null,
        string $status = Payment::STATUS_COMPLETED,
        ?array $gatewayResponse = null,
        ?int $invoiceId = null
    ): Payment {
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'property_id' => $reservation->property_id,
            'invoice_id' => $invoiceId,
            'payment_method' => $paymentMethod,
            'payment_type' => Payment::TYPE_FULL,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'gateway' => $paymentMethod === Payment::METHOD_STRIPE ? 'stripe' : null,
            'gateway_transaction_id' => $gatewayTransactionId,
            'gateway_payment_intent_id' => $gatewayPaymentIntentId,
            'gateway_charge_id' => $gatewayChargeId,
            'gateway_response' => $gatewayResponse,
            'description' => "Payment for Reservation #{$reservation->id}",
            'processed_at' => $status === Payment::STATUS_COMPLETED ? now() : null,
        ]);

        $this->updateReservationPayment($reservation, $amount);

        return $payment;
    }

    /**
     * Update reservation payment status
     */
    protected function updateReservationPayment(Reservation $reservation, float $amount): void
    {
        $reservation->paid_amount = ($reservation->paid_amount ?? 0) + $amount;
        $reservation->balance_due = max(0, $reservation->total_amount - $reservation->paid_amount);
        
        // Update payment status
        if ($reservation->balance_due <= 0) {
            $reservation->payment_status = Reservation::PAYMENT_PAID;
        } elseif ($reservation->paid_amount > 0) {
            $reservation->payment_status = Reservation::PAYMENT_PARTIALLY_PAID;
        }
        
        $reservation->save();
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
                'payment_intent' => $paymentIntent->toArray(),
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}


