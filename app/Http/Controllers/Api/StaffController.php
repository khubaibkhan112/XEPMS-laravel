<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): JsonResponse
    {
        // Check permission
        $user = Auth::guard('web')->user();
        if (!$user->canManageStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to view staff.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|string|in:active,inactive,suspended,terminated',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = User::with(['role', 'assignedProperties']);

            // Filter by property if specified
            if ($request->property_id) {
                $query->whereHas('assignedProperties', function ($q) use ($request) {
                    $q->where('properties.id', $request->property_id);
                })->orWhereHas('properties', function ($q) use ($request) {
                    $q->where('properties.id', $request->property_id);
                });
            }

            // Filter by role
            if ($request->role_id) {
                $query->where('role_id', $request->role_id);
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $perPage = $request->per_page ?? 15;
            $staff = $query->orderBy('name')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching staff.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request): JsonResponse
    {
        // Check permission
        $user = Auth::guard('web')->user();
        if (!$user->canManageStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to create staff.',
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'role_id' => 'required|exists:roles,id',
                'phone' => 'nullable|string|max:50',
                'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
                'hire_date' => 'nullable|date',
                'status' => 'nullable|string|in:active,inactive,suspended,terminated',
                'notes' => 'nullable|string',
                'property_ids' => 'nullable|array',
                'property_ids.*' => 'exists:properties,id',
                'primary_property_id' => 'nullable|exists:properties,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Generate employee ID if not provided
            $employeeId = $request->employee_id ?? 'EMP' . strtoupper(Str::random(8));

            // Create staff member
            $staff = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'phone' => $request->phone,
                'employee_id' => $employeeId,
                'hire_date' => $request->hire_date ?? now(),
                'status' => $request->status ?? 'active',
                'notes' => $request->notes,
            ]);

            // Assign properties if provided
            if ($request->property_ids && count($request->property_ids) > 0) {
                $properties = [];
                foreach ($request->property_ids as $propertyId) {
                    $properties[$propertyId] = [
                        'is_primary' => $request->primary_property_id == $propertyId,
                    ];
                }
                $staff->assignedProperties()->sync($properties);
            }

            $staff->load(['role', 'assignedProperties']);

            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully',
                'data' => $staff,
            ], 201);
        } catch (QueryException $e) {
            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A user with this email already exists.',
                        'errors' => [
                            'email' => ['The email has already been taken.']
                        ],
                    ], 422);
                }
                if (str_contains($e->getMessage(), 'users_employee_id_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A user with this employee ID already exists.',
                        'errors' => [
                            'employee_id' => ['The employee ID has already been taken.']
                        ],
                    ], 422);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please check your input and try again.',
                'errors' => [
                    'database' => ['Unable to create staff member due to database constraint.']
                ],
            ], 422);
        } catch (\Exception $e) {
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
     * Display the specified staff member.
     */
    public function show(string $id): JsonResponse
    {
        // Check permission
        $user = Auth::guard('web')->user();
        if (!$user->canManageStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to view staff.',
            ], 403);
        }

        $staff = User::with(['role', 'role.permissions', 'assignedProperties', 'properties'])
            ->find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Check permission
        $user = Auth::guard('web')->user();
        if (!$user->canManageStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to update staff.',
            ], 403);
        }

        $staff = User::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'sometimes|required|exists:roles,id',
            'phone' => 'nullable|string|max:50',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $id,
            'hire_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,inactive,suspended,terminated',
            'notes' => 'nullable|string',
            'property_ids' => 'nullable|array',
            'property_ids.*' => 'exists:properties,id',
            'primary_property_id' => 'nullable|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = $request->only([
                'name',
                'email',
                'role_id',
                'phone',
                'employee_id',
                'hire_date',
                'status',
                'notes',
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $staff->update($updateData);

            // Update property assignments if provided
            if ($request->has('property_ids')) {
                if (count($request->property_ids) > 0) {
                    $properties = [];
                    foreach ($request->property_ids as $propertyId) {
                        $properties[$propertyId] = [
                            'is_primary' => $request->primary_property_id == $propertyId,
                        ];
                    }
                    $staff->assignedProperties()->sync($properties);
                } else {
                    $staff->assignedProperties()->detach();
                }
            }

            $staff->load(['role', 'role.permissions', 'assignedProperties']);

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully',
                'data' => $staff,
            ]);
        } catch (QueryException $e) {
            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A user with this email already exists.',
                        'errors' => [
                            'email' => ['The email has already been taken.']
                        ],
                    ], 422);
                }
                if (str_contains($e->getMessage(), 'users_employee_id_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A user with this employee ID already exists.',
                        'errors' => [
                            'employee_id' => ['The employee ID has already been taken.']
                        ],
                    ], 422);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please check your input and try again.',
                'errors' => [
                    'database' => ['Unable to update staff member due to database constraint.']
                ],
            ], 422);
        } catch (\Exception $e) {
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
     * Remove the specified staff member.
     */
    public function destroy(string $id): JsonResponse
    {
        // Check permission
        $user = Auth::guard('web')->user();
        if (!$user->canManageStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to delete staff.',
            ], 403);
        }

        $staff = User::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        // Prevent deleting yourself
        if ($staff->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        // Prevent deleting system admin if it's the last admin
        if ($staff->isAdmin() && User::where('role_id', $staff->role_id)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last administrator.',
            ], 422);
        }

        try {
            // Detach property assignments
            $staff->assignedProperties()->detach();

            // Soft delete or hard delete based on your preference
            // For now, we'll just update status to terminated
            $staff->update(['status' => 'terminated']);
            // Or use: $staff->delete(); for soft delete

            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the staff member.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Get all available roles.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::orderBy('level', 'desc')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get all permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get()
            ->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
