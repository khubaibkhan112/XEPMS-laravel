<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of room types for a property.
     */
    public function index(Request $request): JsonResponse
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

        $property = Property::find($request->property_id);

        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $roomTypes = RoomType::where('property_id', $request->property_id)
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypes,
        ]);
    }

    /**
     * Store a newly created room type.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'base_occupancy' => 'nullable|integer|min:1',
            'max_occupancy' => 'nullable|integer|min:1',
            'base_rate' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($request->property_id);

        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check for duplicate code within the property
        if ($request->code) {
            $existingRoomType = RoomType::where('property_id', $request->property_id)
                ->where('code', $request->code)
                ->first();

            if ($existingRoomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'A room type with this code already exists in this property.',
                ], 422);
            }
        }

        try {
            $roomType = RoomType::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Room type created successfully',
                'data' => $roomType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the room type.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Display the specified room type.
     */
    public function show(string $id): JsonResponse
    {
        $roomType = RoomType::with(['property', 'rooms'])
            ->withCount('rooms')
            ->find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Room type not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room type
        if ($roomType->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room type.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $roomType,
        ]);
    }

    /**
     * Update the specified room type.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $roomType = RoomType::with('property')->find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Room type not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room type
        if ($roomType->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room type.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'base_occupancy' => 'nullable|integer|min:1',
            'max_occupancy' => 'nullable|integer|min:1',
            'base_rate' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate code within the property (excluding current room type)
        if ($request->has('code') && $request->code) {
            $existingRoomType = RoomType::where('property_id', $roomType->property_id)
                ->where('code', $request->code)
                ->where('id', '!=', $id)
                ->first();

            if ($existingRoomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'A room type with this code already exists in this property.',
                ], 422);
            }
        }

        $roomType->update($request->only([
            'name',
            'code',
            'base_occupancy',
            'max_occupancy',
            'base_rate',
            'currency',
            'description',
            'amenities',
            'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Room type updated successfully',
            'data' => $roomType,
        ]);
    }

    /**
     * Remove the specified room type.
     */
    public function destroy(string $id): JsonResponse
    {
        $roomType = RoomType::with('property')->find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Room type not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room type
        if ($roomType->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room type.',
            ], 403);
        }

        // Check if room type has rooms
        if ($roomType->rooms()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room type with existing rooms. Please delete or reassign all rooms first.',
            ], 422);
        }

        $roomType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room type deleted successfully',
        ]);
    }
}
