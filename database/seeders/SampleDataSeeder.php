<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run()
    {
        // Brands and models
        $brandId = DB::table('brands')->insertGetId([
            'name_en' => 'Toyota',
            'name_ar' => 'تويوتا',
            'created_at' => now(),
        ]);

        $modelId = DB::table('models')->insertGetId([
            'brand_id' => $brandId,
            'name_en' => 'Corolla',
            'name_ar' => 'كورولا',
            'created_at' => now(),
        ]);

        // Simple media entry (no file upload)
        $mediaId = DB::table('media')->insertGetId([
            'file_name' => 'placeholder.jpg',
            'path' => '/uploads/placeholder.jpg',
            'type' => 'image',
            'status' => 'ready',
            'created_at' => now(),
        ]);

        // Sample ad if an admin user exists
        $user = DB::table('users')->where('email', 'admin@example.com')->first();
        if ($user) {
            $adId = DB::table('ads')->insertGetId([
                'user_id' => $user->id,
                'type' => 'normal',
                'title' => '2015 Toyota Corolla - Sample',
                'description' => 'Sample seeded ad',
                'brand_id' => $brandId,
                'model_id' => $modelId,
                'price_cash' => 8500.00,
                'status' => 'published',
                'created_at' => now(),
            ]);

            DB::table('ad_media')->insert([
                'ad_id' => $adId,
                'media_id' => $mediaId,
                'position' => 1,
            ]);
        }
    }
}
