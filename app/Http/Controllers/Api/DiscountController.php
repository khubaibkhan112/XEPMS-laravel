<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Property;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    /**
     * Display a listing of discounts.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'type' => 'nullable|string|in:percentage,fixed_amount,free_night',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($request->property_id);

        // Ensure user owns the property
        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        $query = Discount::where('property_id', $request->property_id);

        if ($request->has('is_public')) {
            $query->where('is_public', $request->is_public);
        }

        if ($request->has('is_active')) {
            if ($request->is_active) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $discounts = $query->with('roomType')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $discounts,
        ]);
    }

    /**
     * Store a newly created discount.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'code' => 'required|string|max:50|unique:discounts,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:percentage,fixed_amount,free_night',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'room_type_id' => 'nullable|exists:room_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_stay' => 'nullable|integer|min:1',
            'max_stay' => 'nullable|integer|min:1',
            'min_occupancy' => 'nullable|integer|min:1',
            'max_occupancy' => 'nullable|integer|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'applicable_days' => 'nullable|array',
            'applicable_days.*' => 'integer|min:0|max:6',
            'excluded_dates' => 'nullable|array',
            'excluded_dates.*' => 'date',
            'included_dates' => 'nullable|array',
            'included_dates.*' => 'date',
            'loyalty_tier' => 'nullable|string',
            'loyalty_points_required' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($request->property_id);

        // Ensure user owns the property
        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        $discount = Discount::create([
            'property_id' => $request->property_id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'discount_value' => $request->discount_value,
            'max_discount_amount' => $request->max_discount_amount,
            'min_purchase_amount' => $request->min_purchase_amount,
            'currency' => $request->currency ?? $property->currency ?? 'GBP',
            'room_type_id' => $request->room_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'min_stay' => $request->min_stay,
            'max_stay' => $request->max_stay,
            'min_occupancy' => $request->min_occupancy,
            'max_occupancy' => $request->max_occupancy,
            'usage_limit' => $request->usage_limit,
            'usage_limit_per_user' => $request->usage_limit_per_user,
            'is_active' => $request->is_active ?? true,
            'is_public' => $request->is_public ?? true,
            'applicable_days' => $request->applicable_days,
            'excluded_dates' => $request->excluded_dates,
            'included_dates' => $request->included_dates,
            'loyalty_tier' => $request->loyalty_tier,
            'loyalty_points_required' => $request->loyalty_points_required,
            'conditions' => $request->conditions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount created successfully',
            'data' => $discount,
        ], 201);
    }

    /**
     * Display the specified discount.
     */
    public function show(string $id): JsonResponse
    {
        $discount = Discount::with(['property', 'roomType'])->find($id);

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($discount->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this discount.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $discount,
        ]);
    }

    /**
     * Update the specified discount.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $discount = Discount::with('property')->find($id);

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($discount->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this discount.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:50|unique:discounts,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:percentage,fixed_amount,free_night',
            'discount_value' => 'sometimes|required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'room_type_id' => 'nullable|exists:room_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_stay' => 'nullable|integer|min:1',
            'max_stay' => 'nullable|integer|min:1',
            'min_occupancy' => 'nullable|integer|min:1',
            'max_occupancy' => 'nullable|integer|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'applicable_days' => 'nullable|array',
            'excluded_dates' => 'nullable|array',
            'included_dates' => 'nullable|array',
            'loyalty_tier' => 'nullable|string',
            'loyalty_points_required' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper($request->code)]);
        }

        $discount->update($request->only([
            'code',
            'name',
            'description',
            'type',
            'discount_value',
            'max_discount_amount',
            'min_purchase_amount',
            'currency',
            'room_type_id',
            'start_date',
            'end_date',
            'min_stay',
            'max_stay',
            'min_occupancy',
            'max_occupancy',
            'usage_limit',
            'usage_limit_per_user',
            'is_active',
            'is_public',
            'applicable_days',
            'excluded_dates',
            'included_dates',
            'loyalty_tier',
            'loyalty_points_required',
            'conditions',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Discount updated successfully',
            'data' => $discount->fresh(),
        ]);
    }

    /**
     * Remove the specified discount.
     */
    public function destroy(string $id): JsonResponse
    {
        $discount = Discount::with('property')->find($id);

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($discount->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this discount.',
            ], 403);
        }

        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Discount deleted successfully',
        ]);
    }

    /**
     * Validate discount code
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'code' => 'required|string',
            'room_type_id' => 'nullable|exists:room_types,id',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'adult_count' => 'nullable|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $checkIn = $request->check_in ? Carbon::parse($request->check_in) : null;
        $checkOut = $request->check_out ? Carbon::parse($request->check_out) : null;
        $nights = $checkIn && $checkOut ? $checkIn->diffInDays($checkOut) : null;

        $result = $this->pricingService->validateDiscountCode(
            propertyId: $request->property_id,
            discountCode: $request->code,
            roomTypeId: $request->room_type_id,
            checkIn: $checkIn,
            checkOut: $checkOut,
            nights: $nights,
            adultCount: $request->adult_count,
            childCount: $request->child_count,
            subtotal: $request->subtotal
        );

        return response()->json([
            'success' => $result['valid'],
            'data' => $result,
        ], $result['valid'] ? 200 : 422);
    }
}






