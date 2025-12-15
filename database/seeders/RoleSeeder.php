<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin Role - All permissions
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Assign all permissions to Super Admin
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Administrator Role - Most permissions except system settings
        $admin = Role::firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Full property management access',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Assign permissions to Administrator (all except role management and system settings)
        $adminPermissions = Permission::whereNotIn('slug', [
            'roles.create',
            'roles.edit',
            'roles.delete',
            'settings.edit',
        ])->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        // Manager Role - View and manage reservations, check-ins/outs, invoices, payments
        $manager = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Manage reservations, check-ins, check-outs, and financial operations',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $managerPermissions = Permission::whereIn('slug', [
            'properties.view',
            'room-types.view',
            'rooms.view',
            'reservations.view',
            'reservations.create',
            'reservations.edit',
            'reservations.cancel',
            'check-ins.view',
            'check-ins.process',
            'check-outs.view',
            'check-outs.process',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.print',
            'payments.view',
            'payments.process',
            'guests.view',
            'guests.create',
            'guests.edit',
            'staff.view',
            'calendar.view',
            'calendar.manage',
            'reports.view',
            'reports.export',
            'settings.view',
        ])->pluck('id');
        $manager->permissions()->sync($managerPermissions);

        // Receptionist Role - Handle reservations, check-ins, check-outs
        $receptionist = Role::firstOrCreate(
            ['slug' => 'receptionist'],
            [
                'name' => 'Receptionist',
                'description' => 'Handle reservations, check-ins, and check-outs',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $receptionistPermissions = Permission::whereIn('slug', [
            'properties.view',
            'room-types.view',
            'rooms.view',
            'reservations.view',
            'reservations.create',
            'reservations.edit',
            'check-ins.view',
            'check-ins.process',
            'check-outs.view',
            'check-outs.process',
            'invoices.view',
            'invoices.print',
            'payments.view',
            'payments.process',
            'guests.view',
            'guests.create',
            'guests.edit',
            'calendar.view',
            'calendar.manage',
        ])->pluck('id');
        $receptionist->permissions()->sync($receptionistPermissions);

        // Housekeeping Role - View reservations and room status
        $housekeeping = Role::firstOrCreate(
            ['slug' => 'housekeeping'],
            [
                'name' => 'Housekeeping',
                'description' => 'View reservations and manage room status',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $housekeepingPermissions = Permission::whereIn('slug', [
            'properties.view',
            'room-types.view',
            'rooms.view',
            'rooms.edit',
            'reservations.view',
            'calendar.view',
        ])->pluck('id');
        $housekeeping->permissions()->sync($housekeepingPermissions);

        // Accountant Role - Financial operations only
        $accountant = Role::firstOrCreate(
            ['slug' => 'accountant'],
            [
                'name' => 'Accountant',
                'description' => 'Manage invoices and payments',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $accountantPermissions = Permission::whereIn('slug', [
            'reservations.view',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.print',
            'payments.view',
            'payments.process',
            'payments.refund',
            'guests.view',
            'reports.view',
            'reports.export',
        ])->pluck('id');
        $accountant->permissions()->sync($accountantPermissions);

        // Viewer Role - Read-only access
        $viewer = Role::firstOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Viewer',
                'description' => 'Read-only access to view data',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $viewerPermissions = Permission::whereIn('slug', [
            'properties.view',
            'room-types.view',
            'rooms.view',
            'reservations.view',
            'check-ins.view',
            'check-outs.view',
            'invoices.view',
            'payments.view',
            'guests.view',
            'calendar.view',
            'reports.view',
        ])->pluck('id');
        $viewer->permissions()->sync($viewerPermissions);

        $this->command->info('Roles seeded successfully!');
        $this->command->info('Created roles: Super Administrator, Administrator, Manager, Receptionist, Housekeeping, Accountant, Viewer');
    }
}

