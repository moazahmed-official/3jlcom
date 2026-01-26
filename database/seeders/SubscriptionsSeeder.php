<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsSeeder extends Seeder
{
    public function run()
    {
        DB::table('subscriptions')->insertOrIgnore([
            [
                'name' => 'Free',
                'description' => 'Basic free plan',
                'price' => 0,
                'duration_days' => 0,
                'features' => json_encode(['ads_limit' => 5]),
                'available_for_roles' => json_encode(['individual']),
                'status' => 'active'
            ],
            [
                'name' => 'Pro',
                'description' => 'Pro plan with extra limits',
                'price' => 49.99,
                'duration_days' => 30,
                'features' => json_encode(['ads_limit' => 50, 'unique_ads' => 5]),
                'available_for_roles' => json_encode(['seller','marketer']),
                'status' => 'active'
            ]
        ]);
    }
}
