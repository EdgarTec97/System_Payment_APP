<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $adminUser = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@ecommerce-app.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'phone' => '+1234567890',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create support user
        $supportUser = DB::table('users')->insertGetId([
            'name' => 'Support User',
            'first_name' => 'Support',
            'last_name' => 'User',
            'email' => 'support@ecommerce-app.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'phone' => '+1234567891',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create basic user
        $basicUser = DB::table('users')->insertGetId([
            'name' => 'Basic User',
            'first_name' => 'Basic',
            'last_name' => 'User',
            'email' => 'user@ecommerce-app.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'phone' => '+1234567892',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get role IDs
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $supportRole = DB::table('roles')->where('name', 'support')->first();
        $basicRole = DB::table('roles')->where('name', 'basic')->first();

        // Assign roles to users
        DB::table('role_user')->insert([
            [
                'user_id' => $adminUser,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $supportUser,
                'role_id' => $supportRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $basicUser,
                'role_id' => $basicRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create additional basic users for testing
        for ($i = 1; $i <= 5; $i++) {
            $userId = DB::table('users')->insertGetId([
                'name' => "Test User {$i}",
                'first_name' => "Test",
                'last_name' => "User {$i}",
                'email' => "testuser{$i}@example.com",
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => "+123456789{$i}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $basicRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

