<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles first
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator role'
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'description' => 'Regular user role'
        ]);

        $ownerRole = Role::create([
            'name' => 'owner',
            'description' => 'Property owner role'
        ]);

        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'phone' => '1234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'phone' => '0987654321',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
