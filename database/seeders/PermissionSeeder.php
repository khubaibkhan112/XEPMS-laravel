<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Properties Module
            ['name' => 'View Properties', 'slug' => 'properties.view', 'module' => 'properties', 'description' => 'View all properties'],
            ['name' => 'Create Properties', 'slug' => 'properties.create', 'module' => 'properties', 'description' => 'Create new properties'],
            ['name' => 'Edit Properties', 'slug' => 'properties.edit', 'module' => 'properties', 'description' => 'Edit existing properties'],
            ['name' => 'Delete Properties', 'slug' => 'properties.delete', 'module' => 'properties', 'description' => 'Delete properties'],

            // Room Types Module
            ['name' => 'View Room Types', 'slug' => 'room-types.view', 'module' => 'room-types', 'description' => 'View all room types'],
            ['name' => 'Create Room Types', 'slug' => 'room-types.create', 'module' => 'room-types', 'description' => 'Create new room types'],
            ['name' => 'Edit Room Types', 'slug' => 'room-types.edit', 'module' => 'room-types', 'description' => 'Edit existing room types'],
            ['name' => 'Delete Room Types', 'slug' => 'room-types.delete', 'module' => 'room-types', 'description' => 'Delete room types'],

            // Rooms Module
            ['name' => 'View Rooms', 'slug' => 'rooms.view', 'module' => 'rooms', 'description' => 'View all rooms'],
            ['name' => 'Create Rooms', 'slug' => 'rooms.create', 'module' => 'rooms', 'description' => 'Create new rooms'],
            ['name' => 'Edit Rooms', 'slug' => 'rooms.edit', 'module' => 'rooms', 'description' => 'Edit existing rooms'],
            ['name' => 'Delete Rooms', 'slug' => 'rooms.delete', 'module' => 'rooms', 'description' => 'Delete rooms'],

            // Reservations Module
            ['name' => 'View Reservations', 'slug' => 'reservations.view', 'module' => 'reservations', 'description' => 'View all reservations'],
            ['name' => 'Create Reservations', 'slug' => 'reservations.create', 'module' => 'reservations', 'description' => 'Create new reservations'],
            ['name' => 'Edit Reservations', 'slug' => 'reservations.edit', 'module' => 'reservations', 'description' => 'Edit existing reservations'],
            ['name' => 'Cancel Reservations', 'slug' => 'reservations.cancel', 'module' => 'reservations', 'description' => 'Cancel reservations'],
            ['name' => 'Delete Reservations', 'slug' => 'reservations.delete', 'module' => 'reservations', 'description' => 'Delete reservations'],

            // Check-in/Check-out Module
            ['name' => 'View Check-ins', 'slug' => 'check-ins.view', 'module' => 'check-ins', 'description' => 'View all check-ins'],
            ['name' => 'Process Check-ins', 'slug' => 'check-ins.process', 'module' => 'check-ins', 'description' => 'Process guest check-ins'],
            ['name' => 'View Check-outs', 'slug' => 'check-outs.view', 'module' => 'check-outs', 'description' => 'View all check-outs'],
            ['name' => 'Process Check-outs', 'slug' => 'check-outs.process', 'module' => 'check-outs', 'description' => 'Process guest check-outs'],

            // Invoices Module
            ['name' => 'View Invoices', 'slug' => 'invoices.view', 'module' => 'invoices', 'description' => 'View all invoices'],
            ['name' => 'Create Invoices', 'slug' => 'invoices.create', 'module' => 'invoices', 'description' => 'Create new invoices'],
            ['name' => 'Edit Invoices', 'slug' => 'invoices.edit', 'module' => 'invoices', 'description' => 'Edit existing invoices'],
            ['name' => 'Delete Invoices', 'slug' => 'invoices.delete', 'module' => 'invoices', 'description' => 'Delete invoices'],
            ['name' => 'Print Invoices', 'slug' => 'invoices.print', 'module' => 'invoices', 'description' => 'Print invoices'],

            // Payments Module
            ['name' => 'View Payments', 'slug' => 'payments.view', 'module' => 'payments', 'description' => 'View all payments'],
            ['name' => 'Process Payments', 'slug' => 'payments.process', 'module' => 'payments', 'description' => 'Process payments'],
            ['name' => 'Refund Payments', 'slug' => 'payments.refund', 'module' => 'payments', 'description' => 'Process refunds'],

            // Guests Module
            ['name' => 'View Guests', 'slug' => 'guests.view', 'module' => 'guests', 'description' => 'View all guests'],
            ['name' => 'Create Guests', 'slug' => 'guests.create', 'module' => 'guests', 'description' => 'Create new guest profiles'],
            ['name' => 'Edit Guests', 'slug' => 'guests.edit', 'module' => 'guests', 'description' => 'Edit guest profiles'],
            ['name' => 'Delete Guests', 'slug' => 'guests.delete', 'module' => 'guests', 'description' => 'Delete guest profiles'],

            // Staff Management Module
            ['name' => 'View Staff', 'slug' => 'staff.view', 'module' => 'staff', 'description' => 'View all staff members'],
            ['name' => 'Create Staff', 'slug' => 'staff.create', 'module' => 'staff', 'description' => 'Create new staff members'],
            ['name' => 'Edit Staff', 'slug' => 'staff.edit', 'module' => 'staff', 'description' => 'Edit staff members'],
            ['name' => 'Delete Staff', 'slug' => 'staff.delete', 'module' => 'staff', 'description' => 'Delete staff members'],
            ['name' => 'Manage Roles', 'slug' => 'staff.manage-roles', 'module' => 'staff', 'description' => 'Assign and manage staff roles'],

            // Roles & Permissions Module
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles', 'description' => 'View all roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'roles', 'description' => 'Create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'module' => 'roles', 'description' => 'Edit existing roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'roles', 'description' => 'Delete roles'],

            // Channel Connections (OTA) Module
            ['name' => 'View Channels', 'slug' => 'channels.view', 'module' => 'channels', 'description' => 'View channel connections'],
            ['name' => 'Create Channels', 'slug' => 'channels.create', 'module' => 'channels', 'description' => 'Create channel connections'],
            ['name' => 'Edit Channels', 'slug' => 'channels.edit', 'module' => 'channels', 'description' => 'Edit channel connections'],
            ['name' => 'Delete Channels', 'slug' => 'channels.delete', 'module' => 'channels', 'description' => 'Delete channel connections'],
            ['name' => 'Sync Channels', 'slug' => 'channels.sync', 'module' => 'channels', 'description' => 'Sync with channel managers'],

            // Reports Module
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports', 'description' => 'View all reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports', 'description' => 'Export reports'],

            // Calendar Module
            ['name' => 'View Calendar', 'slug' => 'calendar.view', 'module' => 'calendar', 'description' => 'View reservation calendar'],
            ['name' => 'Manage Calendar', 'slug' => 'calendar.manage', 'module' => 'calendar', 'description' => 'Manage calendar events and availability'],

            // Settings Module
            ['name' => 'View Settings', 'slug' => 'settings.view', 'module' => 'settings', 'description' => 'View system settings'],
            ['name' => 'Edit Settings', 'slug' => 'settings.edit', 'module' => 'settings', 'description' => 'Edit system settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                array_merge($permission, ['is_system' => true])
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
}

