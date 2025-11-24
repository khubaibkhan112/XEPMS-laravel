<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms for a property.
     * 
     * @param Request $request
     * @return JsonResponse
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

        // Ensure user owns the property
        if ($property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        $rooms = Room::where('property_id', $request->property_id)
            ->with(['roomType', 'property'])
            ->orderBy('room_number')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Store a newly created room in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:available,occupied,maintenance,out_of_order',
            'floor' => 'nullable|string|max:255',
            'max_occupancy' => 'nullable|integer|min:1|max:255',
            'attributes' => 'nullable|array',
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

        // Ensure user owns the property
        if ($property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        // If room_type_id is provided, ensure it belongs to the same property
        if ($request->room_type_id) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType && $roomType->property_id !== $request->property_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room type does not belong to this property.',
                ], 422);
            }
        }

        // Check for duplicate room_number within the property
        if ($request->room_number) {
            $existingRoom = Room::where('property_id', $request->property_id)
                ->where('room_number', $request->room_number)
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'success' => false,
                    'message' => 'A room with this room number already exists in this property.',
                ], 422);
            }
        }

        $room = Room::create([
            'property_id' => $request->property_id,
            'room_type_id' => $request->room_type_id,
            'name' => $request->name,
            'room_number' => $request->room_number,
            'status' => $request->status ?? 'available',
            'floor' => $request->floor,
            'max_occupancy' => $request->max_occupancy,
            'attributes' => $request->attributes,
            'is_active' => $request->is_active ?? true,
        ]);

        $room->load(['roomType', 'property']);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => $room,
        ], 201);
    }

    /**
     * Display the specified room.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $room = Room::with(['roomType', 'property', 'reservations', 'availability'])
            ->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room
        if ($room->property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $room,
        ]);
    }

    /**
     * Update the specified room in storage.
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $room = Room::with('property')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room
        if ($room->property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'room_type_id' => 'nullable|exists:room_types,id',
            'name' => 'sometimes|required|string|max:255',
            'room_number' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:available,occupied,maintenance,out_of_order',
            'floor' => 'nullable|string|max:255',
            'max_occupancy' => 'nullable|integer|min:1|max:255',
            'attributes' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If room_type_id is provided, ensure it belongs to the same property
        if ($request->has('room_type_id') && $request->room_type_id) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType && $roomType->property_id !== $room->property_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room type does not belong to this property.',
                ], 422);
            }
        }

        // Check for duplicate room_number within the property (excluding current room)
        if ($request->has('room_number') && $request->room_number) {
            $existingRoom = Room::where('property_id', $room->property_id)
                ->where('room_number', $request->room_number)
                ->where('id', '!=', $id)
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'success' => false,
                    'message' => 'A room with this room number already exists in this property.',
                ], 422);
            }
        }

        $room->update($request->only([
            'room_type_id',
            'name',
            'room_number',
            'status',
            'floor',
            'max_occupancy',
            'attributes',
            'is_active',
        ]));

        $room->load(['roomType', 'property']);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => $room,
        ]);
    }

    /**
     * Remove the specified room from storage.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $room = Room::with('property')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
            ], 404);
        }

        // Ensure user owns the property that owns this room
        if ($room->property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this room.',
            ], 403);
        }

        // Check if room has active reservations
        $hasActiveReservations = $room->reservations()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();

        if ($hasActiveReservations) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room with active reservations. Please cancel or complete all reservations first.',
            ], 422);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully',
        ]);
    }
}
