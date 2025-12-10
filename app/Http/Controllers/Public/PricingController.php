<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class PricingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    /**
     * Calculate pricing for a booking
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adult_count' => 'nullable|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'rate_type' => 'nullable|string|in:default,seasonal,weekend,holiday,promotional',
            'features' => 'nullable|array',
            'features.*' => 'integer|min:1', // feature_id => quantity
            'discount_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            $pricing = $this->pricingService->calculateTotalPrice(
                propertyId: $request->property_id,
                roomTypeId: $request->room_type_id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                adultCount: $request->adult_count ?? 1,
                childCount: $request->child_count ?? 0,
                rateType: $request->rate_type ?? 'default',
                selectedFeatures: $request->features ?? [],
                discountCode: $request->discount_code,
                userId: \Illuminate\Support\Facades\Auth::guard('web')->id()
            );

            return response()->json([
                'success' => true,
                'data' => $pricing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating pricing',
                'errors' => [
                    'server' => [$e->getMessage()]
                ],
            ], 500);
        }
    }
}


