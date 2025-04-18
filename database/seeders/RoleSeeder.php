<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'admin',
                'description' => 'Administrator role',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'user',
                'description' => 'Regular user role',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'owner',
                'description' => 'Property owner role',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
} 