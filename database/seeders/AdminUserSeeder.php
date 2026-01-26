<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Creates or updates an admin user (password: "password")
        $data = [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'phone' => '+200000000000',
            'account_type' => 'admin',
            'is_verified' => 1,
            'updated_at' => now(),
        ];

        DB::table('users')->updateOrInsert([
            'email' => 'admin@example.com'
        ], array_merge(['email' => 'admin@example.com', 'created_at' => now()], $data));

        $admin = DB::table('users')->where('email', 'admin@example.com')->first();
        if ($admin) {
            // assign admin role if exists; avoid duplicates
            $role = DB::table('roles')->where('name', 'admin')->first();
            if ($role) {
                DB::table('user_role')->insertOrIgnore([
                    'user_id' => $admin->id,
                    'role_id' => $role->id,
                    'assigned_at' => now(),
                ]);
            }
        }
    }
}
