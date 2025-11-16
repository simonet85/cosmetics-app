<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Create super admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@cosmetics.test'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        // Create customer user
        $customer = User::firstOrCreate(
            ['email' => 'john@example.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $customer->assignRole('customer');
    }
}