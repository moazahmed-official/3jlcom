<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            CountryCitySeeder::class,
            RolesSeeder::class,
            PackagesSeeder::class,
            AdminUserSeeder::class,
            SampleDataSeeder::class,
            SubscriptionsSeeder::class,
            FeaturesSeeder::class,
            CategoriesSeeder::class,
            SpecificationsSeeder::class,
            SavedSearchesSeeder::class,
            ViewsSeeder::class,
            BlogsSeeder::class,
            SlidersSeeder::class,
        ]);
    }
}
