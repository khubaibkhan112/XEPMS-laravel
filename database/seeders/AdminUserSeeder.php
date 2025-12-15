<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $admin = User::where('email', 'admin@xepms.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@xepms.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            // Assign Super Administrator role
            $superAdminRole = Role::where('slug', 'super-admin')->first();
            if ($superAdminRole) {
                $admin->roles()->attach($superAdminRole->id);
            }

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@xepms.com');
            $this->command->info('Password: password');
            $this->command->info('Role: Super Administrator');
        } else {
            // Ensure admin has super-admin role
            $superAdminRole = Role::where('slug', 'super-admin')->first();
            if ($superAdminRole && !$admin->roles()->where('roles.id', $superAdminRole->id)->exists()) {
                $admin->roles()->attach($superAdminRole->id);
                $this->command->info('Super Administrator role assigned to existing admin user.');
            }
            $this->command->warn('Admin user already exists!');
            $this->command->info('Email: admin@xepms.com');
        }
    }
}
