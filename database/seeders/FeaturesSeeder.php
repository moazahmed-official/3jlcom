<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeaturesSeeder extends Seeder
{
    public function run()
    {
        DB::table('features')->insertOrIgnore([
            ['name' => 'Basic Ads', 'description' => 'Allows posting basic ads', 'limits' => json_encode(['normal_ads_limit' => 5]), 'toggles' => json_encode(['auto_republish' => false])],
            ['name' => 'Verified Ads', 'description' => 'Allow verified ads', 'limits' => json_encode([]), 'toggles' => json_encode(['verified_ads_web' => true])]
        ]);
    }
}
