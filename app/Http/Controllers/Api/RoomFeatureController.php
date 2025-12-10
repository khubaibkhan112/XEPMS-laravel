<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomFeature;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoomFeatureController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    /**
     * Display a listing of room features.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'type' => 'nullable|string|in:addon,extra_bed,amenity,service',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
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

        $query = RoomFeature::where('property_id', $request->property_id);

        if ($request->room_type_id) {
            $query->forRoomType($request->room_type_id);
        }

        if ($request->type) {
            $query->ofType($request->type);
        }

        // If dates provided, filter by applicable features
        if ($request->check_in && $request->check_out) {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            $features = $query->active()->orderBy('sort_order')->get();
            $features = $features->filter(function ($feature) use ($request, $nights, $checkIn) {
                return $feature->appliesTo($request->room_type_id, $nights, $checkIn);
            });
        } else {
            $features = $query->active()->orderBy('sort_order')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $features->values(),
        ]);
    }

    /**
     * Store a newly created room feature.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|string|in:addon,extra_bed,amenity,service',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'pricing_type' => 'required|string|in:per_night,per_stay,per_person,per_person_per_night',
            'currency' => 'nullable|string|size:3',
            'max_quantity' => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
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

        $feature = RoomFeature::create([
            'property_id' => $request->property_id,
            'room_type_id' => $request->room_type_id,
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'description' => $request->description,
            'price' => $request->price,
            'pricing_type' => $request->pricing_type,
            'currency' => $request->currency ?? $property->currency ?? 'GBP',
            'max_quantity' => $request->max_quantity,
            'is_required' => $request->is_required ?? false,
            'is_active' => $request->is_active ?? true,
            'sort_order' => $request->sort_order ?? 0,
            'conditions' => $request->conditions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room feature created successfully',
            'data' => $feature,
        ], 201);
    }

    /**
     * Display the specified room feature.
     */
    public function show(string $id): JsonResponse
    {
        $feature = RoomFeature::with(['property', 'roomType'])->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Room feature not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($feature->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this feature.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $feature,
        ]);
    }

    /**
     * Update the specified room feature.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $feature = RoomFeature::with('property')->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Room feature not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($feature->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this feature.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'sometimes|required|string|in:addon,extra_bed,amenity,service',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'pricing_type' => 'sometimes|required|string|in:per_night,per_stay,per_person,per_person_per_night',
            'currency' => 'nullable|string|size:3',
            'max_quantity' => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feature->update($request->only([
            'name',
            'code',
            'type',
            'description',
            'price',
            'pricing_type',
            'currency',
            'max_quantity',
            'is_required',
            'is_active',
            'sort_order',
            'conditions',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Room feature updated successfully',
            'data' => $feature->fresh(),
        ]);
    }

    /**
     * Remove the specified room feature.
     */
    public function destroy(string $id): JsonResponse
    {
        $feature = RoomFeature::with('property')->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Room feature not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($feature->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this feature.',
            ], 403);
        }

        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room feature deleted successfully',
        ]);
    }

    /**
     * Get available features for a booking
     */
    public function getAvailableFeatures(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($request->property_id);

        // Ensure user owns the property (for admin) or allow public access
        if (Auth::guard('web')->check() && $property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        $checkIn = $request->check_in ? Carbon::parse($request->check_in) : null;
        $checkOut = $request->check_out ? Carbon::parse($request->check_out) : null;

        $features = $this->pricingService->getAvailableFeatures(
            propertyId: $property->id,
            roomTypeId: $request->room_type_id,
            checkIn: $checkIn,
            checkOut: $checkOut
        );

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }
}



