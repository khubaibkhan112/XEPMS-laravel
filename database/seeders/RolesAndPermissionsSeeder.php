<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Property Management
            ['name' => 'View Properties', 'slug' => 'properties.view', 'group' => 'properties'],
            ['name' => 'Create Properties', 'slug' => 'properties.create', 'group' => 'properties'],
            ['name' => 'Edit Properties', 'slug' => 'properties.edit', 'group' => 'properties'],
            ['name' => 'Delete Properties', 'slug' => 'properties.delete', 'group' => 'properties'],

            // Room Management
            ['name' => 'View Rooms', 'slug' => 'rooms.view', 'group' => 'rooms'],
            ['name' => 'Create Rooms', 'slug' => 'rooms.create', 'group' => 'rooms'],
            ['name' => 'Edit Rooms', 'slug' => 'rooms.edit', 'group' => 'rooms'],
            ['name' => 'Delete Rooms', 'slug' => 'rooms.delete', 'group' => 'rooms'],

            // Reservation Management
            ['name' => 'View Reservations', 'slug' => 'reservations.view', 'group' => 'reservations'],
            ['name' => 'Create Reservations', 'slug' => 'reservations.create', 'group' => 'reservations'],
            ['name' => 'Edit Reservations', 'slug' => 'reservations.edit', 'group' => 'reservations'],
            ['name' => 'Cancel Reservations', 'slug' => 'reservations.cancel', 'group' => 'reservations'],
            ['name' => 'Delete Reservations', 'slug' => 'reservations.delete', 'group' => 'reservations'],

            // Guest Management
            ['name' => 'View Guests', 'slug' => 'guests.view', 'group' => 'guests'],
            ['name' => 'Create Guests', 'slug' => 'guests.create', 'group' => 'guests'],
            ['name' => 'Edit Guests', 'slug' => 'guests.edit', 'group' => 'guests'],
            ['name' => 'Delete Guests', 'slug' => 'guests.delete', 'group' => 'guests'],

            // Check-in/Check-out
            ['name' => 'Check-in Guests', 'slug' => 'checkin.process', 'group' => 'operations'],
            ['name' => 'Check-out Guests', 'slug' => 'checkout.process', 'group' => 'operations'],
            ['name' => 'View Check-ins', 'slug' => 'checkin.view', 'group' => 'operations'],
            ['name' => 'View Check-outs', 'slug' => 'checkout.view', 'group' => 'operations'],

            // Payments
            ['name' => 'View Payments', 'slug' => 'payments.view', 'group' => 'payments'],
            ['name' => 'Process Payments', 'slug' => 'payments.process', 'group' => 'payments'],
            ['name' => 'Process Refunds', 'slug' => 'payments.refund', 'group' => 'payments'],

            // Staff Management
            ['name' => 'View Staff', 'slug' => 'staff.view', 'group' => 'staff'],
            ['name' => 'Create Staff', 'slug' => 'staff.create', 'group' => 'staff'],
            ['name' => 'Edit Staff', 'slug' => 'staff.edit', 'group' => 'staff'],
            ['name' => 'Delete Staff', 'slug' => 'staff.delete', 'group' => 'staff'],
            ['name' => 'Manage Staff', 'slug' => 'staff.manage', 'group' => 'staff'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'View Financial Reports', 'slug' => 'reports.financial', 'group' => 'reports'],

            // Settings
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'settings'],

            // Channel Manager
            ['name' => 'View Channel Manager', 'slug' => 'channels.view', 'group' => 'channels'],
            ['name' => 'Manage Channel Manager', 'slug' => 'channels.manage', 'group' => 'channels'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create Roles
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access with all permissions',
                'is_system' => true,
                'level' => 100,
                'permissions' => Permission::pluck('id')->toArray(), // All permissions
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Property manager with most permissions except staff management',
                'is_system' => true,
                'level' => 80,
                'permissions' => [
                    'properties.view', 'properties.create', 'properties.edit', 'properties.delete',
                    'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
                    'reservations.view', 'reservations.create', 'reservations.edit', 'reservations.cancel',
                    'guests.view', 'guests.create', 'guests.edit',
                    'checkin.process', 'checkout.process', 'checkin.view', 'checkout.view',
                    'payments.view', 'payments.process', 'payments.refund',
                    'reports.view', 'reports.financial',
                    'channels.view', 'channels.manage',
                ],
            ],
            [
                'name' => 'Receptionist',
                'slug' => 'receptionist',
                'description' => 'Front desk staff with reservation and check-in/out access',
                'is_system' => true,
                'level' => 50,
                'permissions' => [
                    'properties.view',
                    'rooms.view',
                    'reservations.view', 'reservations.create', 'reservations.edit',
                    'guests.view', 'guests.create', 'guests.edit',
                    'checkin.process', 'checkout.process', 'checkin.view', 'checkout.view',
                    'payments.view', 'payments.process',
                ],
            ],
            [
                'name' => 'Housekeeping',
                'slug' => 'housekeeping',
                'description' => 'Housekeeping staff with room status access',
                'is_system' => true,
                'level' => 30,
                'permissions' => [
                    'properties.view',
                    'rooms.view', 'rooms.edit',
                    'reservations.view',
                ],
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'General staff member with limited access',
                'is_system' => true,
                'level' => 20,
                'permissions' => [
                    'properties.view',
                    'rooms.view',
                    'reservations.view',
                    'guests.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionSlugs = $roleData['permissions'] ?? [];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Assign permissions to role
            if (!empty($permissionSlugs)) {
                $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->toArray();
                $role->assignPermissions($permissionIds);
            }
        }
    }
}
