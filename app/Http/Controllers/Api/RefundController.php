<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefundPolicy;
use App\Models\Reservation;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {
    }

    /**
     * Calculate refund for a reservation
     */
    public function calculate(Request $request, int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Verify user has access
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $cancellationDate = $request->cancellation_date 
                ? Carbon::parse($request->cancellation_date) 
                : Carbon::now();
            
            $result = $this->refundService->calculateRefund(
                reservation: $reservation,
                cancellationDate: $cancellationDate,
                cancellationReason: $request->cancellation_reason
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating refund',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process refund for a reservation
     */
    public function process(Request $request, int $reservationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
            'cancellation_date' => 'nullable|date',
            'automatic' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Verify user has access
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $cancellationDate = $request->cancellation_date 
                ? Carbon::parse($request->cancellation_date) 
                : null;

            $result = $this->refundService->processRefund(
                reservation: $reservation,
                amount: $request->amount ? (float)$request->amount : null,
                reason: $request->reason,
                cancellationDate: $cancellationDate,
                automatic: $request->automatic ?? false
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process refund',
                'data' => $result,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing refund',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get refund history for a reservation
     */
    public function history(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Verify user has access
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $history = $this->refundService->getRefundHistory($reservation);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching refund history',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List refund policies for a property
     */
    public function policies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $property = \App\Models\Property::findOrFail($request->property_id);
            
            // Verify user has access
            if (Auth::check() && $property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $policies = RefundPolicy::where('property_id', $request->property_id)
                ->active()
                ->orderBy('priority', 'asc')
                ->orderBy('is_default', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $policies,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching refund policies',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create refund policy
     */
    public function createPolicy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'refund_type' => 'required|string|in:percentage,fixed_amount,full',
            'refund_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'days_before_checkin' => 'nullable|integer|min:0',
            'days_after_booking' => 'nullable|integer|min:0',
            'minimum_nights' => 'nullable|integer|min:1',
            'requires_cancellation_reason' => 'nullable|boolean',
            'allowed_cancellation_reasons' => 'nullable|array',
            'is_default' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $property = \App\Models\Property::findOrFail($request->property_id);
            
            // Verify user has access
            if (Auth::check() && $property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // If setting as default, unset other defaults
            if ($request->is_default) {
                RefundPolicy::where('property_id', $request->property_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $policy = RefundPolicy::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $policy,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating refund policy',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update refund policy
     */
    public function updatePolicy(Request $request, int $policyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'refund_type' => 'nullable|string|in:percentage,fixed_amount,full',
            'refund_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'days_before_checkin' => 'nullable|integer|min:0',
            'days_after_booking' => 'nullable|integer|min:0',
            'minimum_nights' => 'nullable|integer|min:1',
            'requires_cancellation_reason' => 'nullable|boolean',
            'allowed_cancellation_reasons' => 'nullable|array',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $policy = RefundPolicy::findOrFail($policyId);
            
            // Verify user has access
            if (Auth::check() && $policy->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // If setting as default, unset other defaults
            if ($request->is_default && !$policy->is_default) {
                RefundPolicy::where('property_id', $policy->property_id)
                    ->where('id', '!=', $policyId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $policy->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $policy->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating refund policy',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete refund policy
     */
    public function deletePolicy(int $policyId): JsonResponse
    {
        try {
            $policy = RefundPolicy::findOrFail($policyId);
            
            // Verify user has access
            if (Auth::check() && $policy->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Refund policy deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting refund policy',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
