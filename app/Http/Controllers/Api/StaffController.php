<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    /**
     * Display a listing of staff
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|exists:roles,id',
            'department' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
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
            $query = User::with(['roles', 'defaultProperty']);

            // Filter by property if specified
            if ($request->property_id) {
                $query->where('property_id', $request->property_id);
            }

            // Search
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%')
                        ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            }

            // Filter by role
            if ($request->role) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('roles.id', $request->role);
                });
            }

            // Filter by department
            if ($request->department) {
                $query->where('department', $request->department);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $perPage = $request->per_page ?? 15;
            $staff = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $staff,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching staff',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created staff member
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'property_id' => 'nullable|exists:properties,id',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'position' => $request->position,
                'department' => $request->department,
                'property_id' => $request->property_id,
                'notes' => $request->notes,
                'is_active' => $request->is_active ?? true,
                'password_changed_at' => now(),
            ]);

            // Assign roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $propertyId = $request->property_id ?? null;
                foreach ($request->roles as $roleId) {
                    $user->roles()->attach($roleId, ['property_id' => $propertyId]);
                }
            }

            DB::commit();

            $user->load(['roles', 'defaultProperty']);

            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating staff member',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified staff member
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = User::with(['roles', 'defaultProperty', 'properties'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Update the specified staff member
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'property_id' => 'nullable|exists:properties,id',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);

            $updateData = $request->only([
                'name', 'email', 'phone', 'position', 'department',
                'property_id', 'notes', 'is_active'
            ]);

            // Update password if provided
            if ($request->has('password') && $request->password) {
                $updateData['password'] = Hash::make($request->password);
                $updateData['password_changed_at'] = now();
            }

            $user->update($updateData);

            // Update roles if provided
            if ($request->has('roles')) {
                $propertyId = $request->property_id ?? $user->property_id;
                
                // Remove existing roles
                $user->roles()->detach();
                
                // Attach new roles
                if (is_array($request->roles)) {
                    foreach ($request->roles as $roleId) {
                        $user->roles()->attach($roleId, ['property_id' => $propertyId]);
                    }
                }
            }

            DB::commit();

            $user->load(['roles', 'defaultProperty']);

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating staff member',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified staff member
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting the current user
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account',
                ], 403);
            }

            // Remove roles before deleting
            $user->roles()->detach();

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting staff member',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function roles(): JsonResponse
    {
        try {
            $roles = Role::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching roles',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all available permissions
     */
    public function permissions(): JsonResponse
    {
        try {
            $permissions = \App\Models\Permission::orderBy('module')
                ->orderBy('name')
                ->get()
                ->groupBy('module');

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching permissions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get staff member's roles and permissions
     */
    public function getRolesAndPermissions(string $id): JsonResponse
    {
        try {
            $user = User::with(['roles.permissions'])->findOrFail($id);

            $roles = $user->roles;
            $permissions = $user->permissions()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching roles and permissions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Assign role to staff member
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            $propertyId = $request->property_id ?? $user->property_id;

            // Check if role is already assigned
            $exists = $user->roles()
                ->where('roles.id', $request->role_id)
                ->wherePivot('property_id', $propertyId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role is already assigned to this user',
                ], 400);
            }

            $user->roles()->attach($request->role_id, ['property_id' => $propertyId]);

            $user->load(['roles', 'defaultProperty']);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove role from staff member
     */
    public function removeRole(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            $propertyId = $request->property_id ?? $user->property_id;

            $user->roles()->wherePivot('property_id', $propertyId)
                ->detach($request->role_id);

            $user->load(['roles', 'defaultProperty']);

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing role',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}


