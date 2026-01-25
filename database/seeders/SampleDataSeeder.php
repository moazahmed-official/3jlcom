<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        // Brands and models
        $brands = [];
        for ($i = 1; $i <= 5; $i++) {
            $brandId = DB::table('brands')->insertGetId([
                'name_en' => $faker->company . ' ' . $i,
                'name_ar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $brands[] = $brandId;

            for ($m = 1; $m <= 3; $m++) {
                DB::table('models')->insert([
                    'brand_id' => $brandId,
                    'name_en' => $faker->word . ' ' . $m,
                    'name_ar' => null,
                    'year_from' => 2000 + rand(0, 10),
                    'year_to' => 2010 + rand(0, 15),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Media
        $mediaIds = [];
        for ($i = 0; $i < 10; $i++) {
            $id = DB::table('media')->insertGetId([
                'url' => 'https://picsum.photos/seed/' . ($i + 1) . '/800/600',
                'thumbnail_url' => 'https://picsum.photos/seed/thumb' . ($i + 1) . '/200/150',
                'type' => 'image',
                'status' => 'active',
                'meta' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mediaIds[] = $id;
        }

        // Sample normal ads
        $users = DB::table('users')->limit(10)->pluck('id')->toArray();
        if (empty($users)) {
            // create a few lightweight users
            for ($u = 0; $u < 5; $u++) {
                $users[] = DB::table('users')->insertGetId([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => bcrypt('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $country = DB::table('countries')->first();
        $city = DB::table('cities')->first();
        $categories = DB::table('categories')->limit(5)->pluck('id')->toArray();
        if (empty($categories)) {
            $defaultCategories = ['Vehicles','Electronics','Real Estate','Jobs','Services'];
            foreach ($defaultCategories as $slug => $name) {
                $categories[] = DB::table('categories')->insertGetId([
                    'slug' => is_string($slug) ? strtolower(str_replace(' ', '_', $name)) : strtolower($name),
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        for ($i = 0; $i < 15; $i++) {
            DB::table('normal_ads')->insert([
                'user_id' => $users[array_rand($users)],
                'title' => ucfirst($faker->words(4, true)),
                'description' => $faker->paragraph(),
                'category_id' => $categories[array_rand($categories)] ?? 1,
                'city_id' => $city->id ?? null,
                'country_id' => $country->id ?? null,
                'brand_id' => $brands[array_rand($brands)],
                'model_id' => DB::table('models')->where('brand_id', $brands[array_rand($brands)])->value('id'),
                'year' => rand(2005, 2022),
                'price_cash' => $faker->randomFloat(2, 100, 50000),
                'banner_image_id' => $mediaIds[array_rand($mediaIds)],
                'status' => 'active',
                'is_published' => 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Auctions
        for ($i = 0; $i < 5; $i++) {
            $auctionId = DB::table('auctions')->insertGetId([
                'user_id' => $users[array_rand($users)],
                'title' => 'Auction: ' . $faker->words(3, true),
                'start_price' => $faker->randomFloat(2, 50, 2000),
                'reserve_price' => $faker->randomFloat(2, 2000, 10000),
                'start_time' => now()->addDays(rand(-10, 0)),
                'end_time' => now()->addDays(rand(1, 30)),
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // bids
            for ($b = 0; $b < rand(1, 6); $b++) {
                DB::table('auction_bids')->insert([
                    'auction_id' => $auctionId,
                    'user_id' => $users[array_rand($users)],
                    'price' => $faker->randomFloat(2, 60, 12000),
                    'comment' => $faker->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
