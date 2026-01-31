<?php

namespace Database\Seeders;

use App\Models\FinditRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FindItRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user (create if none exists)
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Create sample FindIt requests without brand references for now
        $requests = [
            [
                'user_id' => $user->id,
                'title' => 'Looking for Sedan 2020-2023',
                'description' => 'Need a reliable family sedan. Preferably white or silver color. Low mileage preferred.',
                'brand_id' => null,
                'model_id' => null,
                'min_price' => 15000,
                'max_price' => 25000,
                'min_year' => 2020,
                'max_year' => 2023,
                'min_mileage' => null,
                'max_mileage' => 50000,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'condition' => 'used',
                'status' => 'active',
                'expires_at' => Carbon::now()->addDays(30),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Searching for Sports Sedan',
                'description' => 'Looking for a sporty sedan. Sport package preferred.',
                'brand_id' => null,
                'model_id' => null,
                'min_price' => 30000,
                'max_price' => 50000,
                'min_year' => 2019,
                'max_year' => 2024,
                'min_mileage' => null,
                'max_mileage' => 60000,
                'transmission' => 'automatic',
                'fuel_type' => null,
                'condition' => null,
                'status' => 'active',
                'expires_at' => Carbon::now()->addDays(45),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Electric Vehicle Wanted',
                'description' => 'Looking for any electric vehicle with good range.',
                'brand_id' => null,
                'model_id' => null,
                'min_price' => 20000,
                'max_price' => 60000,
                'min_year' => 2021,
                'max_year' => null,
                'min_mileage' => null,
                'max_mileage' => 40000,
                'transmission' => null,
                'fuel_type' => 'electric',
                'condition' => null,
                'status' => 'active',
                'expires_at' => Carbon::now()->addDays(60),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Budget SUV for Family',
                'description' => 'Need a spacious SUV for family trips. Good condition required.',
                'brand_id' => null,
                'model_id' => null,
                'min_price' => 10000,
                'max_price' => 20000,
                'min_year' => 2018,
                'max_year' => 2022,
                'min_mileage' => null,
                'max_mileage' => 80000,
                'transmission' => null,
                'fuel_type' => null,
                'body_type' => 'suv',
                'condition' => 'used',
                'status' => 'draft',
                'expires_at' => Carbon::now()->addDays(30),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Luxury Sedan - Premium',
                'description' => 'Looking for a premium sedan with full options. Must be in excellent condition.',
                'brand_id' => null,
                'model_id' => null,
                'min_price' => 40000,
                'max_price' => 80000,
                'min_year' => 2020,
                'max_year' => null,
                'min_mileage' => null,
                'max_mileage' => 30000,
                'transmission' => 'automatic',
                'fuel_type' => null,
                'condition' => 'certified',
                'status' => 'active',
                'expires_at' => Carbon::now()->addDays(20),
            ],
        ];

        foreach ($requests as $requestData) {
            FinditRequest::create($requestData);
        }

        $this->command->info('Created ' . count($requests) . ' FindIt requests.');
    }
}
