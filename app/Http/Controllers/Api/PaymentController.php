<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Create a payment intent for a reservation
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify user has access to this reservation's property
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this reservation',
                ], 403);
            }

            $amount = (float)$request->amount;
            $currency = $request->currency ?? $reservation->currency ?? 'GBP';

            $result = $this->paymentService->createPaymentIntent(
                reservation: $reservation,
                amount: $amount,
                currency: $currency,
                metadata: $request->metadata ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to create payment intent',
                'error_code' => $result['error_code'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the payment intent.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm a payment
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:stripe,paypal,bank_transfer,cash',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify user has access to this reservation's property
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this reservation',
                ], 403);
            }

            $amount = $request->amount ? (float)$request->amount : null;
            $paymentMethod = $request->payment_method ?? Payment::METHOD_STRIPE;

            $result = $this->paymentService->confirmPayment(
                paymentIntentId: $request->payment_intent_id,
                reservation: $reservation,
                amount: $amount,
                paymentMethod: $paymentMethod
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to confirm payment',
                'error_code' => $result['error_code'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming the payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process a payment
     */
    public function process(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:stripe,paypal,bank_transfer,cash',
            'payment_type' => 'nullable|string|in:full,partial,deposit',
            'payment_intent_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify user has access to this reservation's property
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this reservation',
                ], 403);
            }

            $result = $this->paymentService->processPayment(
                reservation: $reservation,
                amount: (float)$request->amount,
                paymentMethod: $request->payment_method,
                paymentType: $request->payment_type ?? Payment::TYPE_FULL,
                paymentIntentId: $request->payment_intent_id,
                metadata: $request->metadata ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to process payment',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->getPaymentStatus($request->payment_intent_id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Refund a payment
     */
    public function refund(Request $request, int $paymentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = Payment::findOrFail($paymentId);
            
            // Verify user has access to this payment's property
            if (Auth::check() && $payment->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment',
                ], 403);
            }

            $amount = $request->amount ? (float)$request->amount : null;
            $reason = $request->reason ?? 'requested_by_customer';

            $result = $this->paymentService->refundPayment(
                payment: $payment,
                amount: $amount,
                reason: $reason
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to process refund',
                'error_code' => $result['error_code'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the refund.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List payments for a reservation
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify user has access to this reservation's property
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this reservation',
                ], 403);
            }

            $payments = Payment::where('reservation_id', $reservation->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching payments.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        $methods = [
            [
                'value' => Payment::METHOD_STRIPE,
                'label' => 'Credit Card (Stripe)',
                'gateway' => 'stripe',
                'requires_gateway' => true,
            ],
            [
                'value' => Payment::METHOD_PAYPAL,
                'label' => 'PayPal',
                'gateway' => 'paypal',
                'requires_gateway' => true,
            ],
            [
                'value' => Payment::METHOD_BANK_TRANSFER,
                'label' => 'Bank Transfer',
                'gateway' => null,
                'requires_gateway' => false,
            ],
            [
                'value' => Payment::METHOD_CASH,
                'label' => 'Cash',
                'gateway' => null,
                'requires_gateway' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Get a specific payment
     */
    public function show(int $paymentId): JsonResponse
    {
        try {
            $payment = Payment::with(['reservation', 'property'])->findOrFail($paymentId);
            
            // Verify user has access to this payment's property
            if (Auth::check() && $payment->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }
}
