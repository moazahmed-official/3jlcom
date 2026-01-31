<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ad;
use App\Models\Auction;
use App\Models\User;

class AuctionAdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user for the auction ads
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating sample auction ads...');

        // Create 5 sample auction ads
        for ($i = 1; $i <= 5; $i++) {
            // Create the base ad
            $ad = Ad::create([
                'user_id' => $user->id,
                'type' => 'auction',
                'title' => "Vintage Watch Collection #{$i}",
                'description' => "Beautiful vintage Rolex watch #{$i} in excellent condition. This is a rare piece perfect for collectors.",
                'category_id' => 1, // Assuming category 1 exists
                'brand_id' => 1,    // Assuming brand 1 exists
                'city_id' => 1,     // Assuming city 1 exists
                'country_id' => 1,  // Assuming country 1 exists
                'status' => 'published',
                'published_at' => now(),
                'views_count' => rand(10, 100),
                'contact_count' => rand(0, 10),
                'contact_phone' => '+1234567890',
                'whatsapp_number' => '+1234567890',
                'period_days' => 30,
            ]);

            // Create the auction details
            Auction::create([
                'ad_id' => $ad->id,
                'start_price' => 50000 + ($i * 10000),
                'minimum_bid_increment' => 500,
                'start_time' => now()->subHours($i), // Started in the past
                'end_time' => now()->addDays(7 + $i),
                'anti_snip_window_seconds' => 300,
                'anti_snip_extension_seconds' => 300,
                'status' => 'active',
                'bid_count' => 0,
                'last_price' => null,
            ]);

            $this->command->info("Created auction ad #{$i} with ID: {$ad->id}");
        }

        $this->command->info('Sample auction ads created successfully!');
    }
}
