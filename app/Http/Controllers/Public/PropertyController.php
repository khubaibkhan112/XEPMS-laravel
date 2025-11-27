<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    /**
     * Search and list public properties
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Property::where('is_active', true)
            ->with(['roomTypes' => function ($q) {
                $q->where('is_active', true);
            }])
            ->withCount(['rooms' => function ($q) {
                $q->where('is_active', true);
            }]);

        // Search by name or address
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Search by location/address
        if ($request->location) {
            $location = $request->location;
            $query->where(function ($q) use ($location) {
                $q->where('address', 'like', "%{$location}%");
            });
        }

        $properties = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    /**
     * Get public property details
     */
    public function show(string $id): JsonResponse
    {
        $property = Property::where('is_active', true)
            ->with(['roomTypes' => function ($q) {
                $q->where('is_active', true);
            }])
            ->withCount(['rooms' => function ($q) {
                $q->where('is_active', true);
            }])
            ->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $property,
        ]);
    }

    /**
     * List all active properties
     */
    public function index(): JsonResponse
    {
        $properties = Property::where('is_active', true)
            ->with(['roomTypes' => function ($q) {
                $q->where('is_active', true)->limit(5);
            }])
            ->withCount(['rooms' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }
}

