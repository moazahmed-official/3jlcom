<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'user',
                'display_name' => 'User',
                'permissions' => json_encode(['ads.create', 'ads.read', 'ads.edit.own']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'seller',
                'display_name' => 'Seller',
                'permissions' => json_encode(['ads.create', 'ads.read', 'ads.edit.own', 'ads.manage.own']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'showroom',
                'display_name' => 'Showroom',
                'permissions' => json_encode(['ads.create', 'ads.read', 'ads.edit.own', 'ads.manage.own', 'offers.create']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'marketer',
                'display_name' => 'Marketer',
                'permissions' => json_encode(['ads.create', 'ads.read', 'campaigns.create', 'campaigns.manage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'permissions' => json_encode(['users.manage', 'ads.manage', 'packages.manage', 'categories.manage', 'system.config']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'permissions' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'individual',
                'display_name' => 'Individual',
                'permissions' => json_encode(['ads.create', 'ads.read', 'ads.edit.own']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}