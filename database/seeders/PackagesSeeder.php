<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagesSeeder extends Seeder
{
    public function run()
    {
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
