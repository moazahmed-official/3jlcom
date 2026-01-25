<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        // Insert admin user — adapt to existing users table columns (email or phone)
        if (SchemaExists('users', 'email')) {
            DB::table('users')->updateOrInsert(
                ['email' => 'admin@example.com'],
                ['name' => 'Admin', 'password' => Hash::make('secret'), 'created_at' => $now, 'updated_at' => $now]
            );
            $user = DB::table('users')->where('email', 'admin@example.com')->first();
        } elseif (SchemaExists('users', 'phone')) {
            DB::table('users')->updateOrInsert(
                ['phone' => '0000000000'],
                ['name' => 'Admin', 'password' => Hash::make('secret'), 'created_at' => $now, 'updated_at' => $now]
            );
            $user = DB::table('users')->where('phone', '0000000000')->first();
        } else {
            // fallback: insert minimal user if schema unknown
            DB::table('users')->insertOrIgnore(['name' => 'Admin', 'password' => Hash::make('secret'), 'created_at' => $now, 'updated_at' => $now]);
            $user = DB::table('users')->where('name', 'Admin')->first();
        }
        $role = DB::table('roles')->where('name', 'admin')->first();
        if ($user && $role) {
            DB::table('role_user')->updateOrInsert(['user_id' => $user->id, 'role_id' => $role->id], ['created_at' => $now, 'updated_at' => $now]);
        }
    }
}

function SchemaExists(string $table, string $column): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Exception $e) {
        return false;
    }
}
