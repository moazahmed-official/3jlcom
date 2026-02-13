<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagesSeeder extends Seeder
{
    public function run()
    {
        // Standard Package - Default free package for all new users
        DB::table('packages')->updateOrInsert(
            ['name' => 'Standard Package'],
            [
                'description' => 'Free standard package for all users with basic features',
                'price' => 0.00,
                'duration_days' => 365,
                'active' => true,
                'visibility_type' => 'public',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Other packages
        DB::table('packages')->insertOrIgnore([
            [
                'name' => 'Dealer Pro',
                'description' => '50 normal ads + 10 featured',
                'price' => 199.99,
                'duration_days' => 30,
                'features' => json_encode(['normal_ads' => 50, 'featured_ads' => 10]),
                'created_at' => now(),
            ],
        ]);
    }
}
