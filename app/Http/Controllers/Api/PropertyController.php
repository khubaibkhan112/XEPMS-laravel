<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $properties = Property::where('user_id', Auth::guard('web')->id())
            ->withCount(['rooms', 'reservations'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    /**
     * Store a newly created property.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:properties,code',
                'timezone' => 'nullable|string|max:50',
                'currency' => 'nullable|string|size:3',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'settings' => 'nullable|array',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $property = Property::create([
                'user_id' => Auth::guard('web')->id(),
                'name' => $request->name,
                'code' => $request->code,
                'timezone' => $request->timezone ?? 'UTC',
                'currency' => $request->currency ?? 'GBP',
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'settings' => $request->settings,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully',
                'data' => $property,
            ], 201);
        } catch (QueryException $e) {
            // Handle database errors (e.g., duplicate entries, constraint violations)
            $errorCode = $e->getCode();
            
            if ($errorCode === '23000') { // Integrity constraint violation
                // Check if it's a duplicate code
                if (str_contains($e->getMessage(), 'properties_code_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A property with this code already exists.',
                        'errors' => [
                            'code' => ['The property code has already been taken.']
                        ],
                    ], 422);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Database error occurred. Please check your input and try again.',
                    'errors' => [
                        'database' => ['Unable to create property due to database constraint.']
                    ],
                ], 422);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the property.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Display the specified property.
     */
    public function show(string $id): JsonResponse
    {
        $property = Property::where('user_id', Auth::guard('web')->id())
            ->withCount(['rooms', 'reservations'])
            ->with(['roomTypes', 'rooms'])
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
     * Update the specified property.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $property = Property::where('user_id', Auth::guard('web')->id())->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:properties,code,' . $id,
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property->update($request->only([
            'name',
            'code',
            'timezone',
            'currency',
            'email',
            'phone',
            'address',
            'settings',
            'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Property updated successfully',
            'data' => $property,
        ]);
    }

    /**
     * Remove the specified property.
     */
    public function destroy(string $id): JsonResponse
    {
        $property = Property::where('user_id', Auth::guard('web')->id())->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        // Check if property has rooms or reservations
        if ($property->rooms()->count() > 0 || $property->reservations()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete property with rooms or reservations. Please delete all rooms and reservations first.',
            ], 422);
        }

        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'Property deleted successfully',
        ]);
    }
}
