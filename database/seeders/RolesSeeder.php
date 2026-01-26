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
                'name' => 'individual',
                'display_name' => 'Individual User',
                'permissions' => json_encode(['ads.create','ads.read']),
                'created_at' => now(),
            ],
            [
                'name' => 'dealer',
                'display_name' => 'Dealer',
                'permissions' => json_encode(['ads.create','offers.create']),
                'created_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'permissions' => json_encode(['users.manage','ads.manage','packages.manage']),
                'created_at' => now(),
            ],
        ]);
    }
}
