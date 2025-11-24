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

        if ($property->user_id !== Auth::id()) {
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

        if ($property->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $roomType = RoomType::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Room type created successfully',
            'data' => $roomType,
        ], 201);
    }
}
