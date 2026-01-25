<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryCitySeeder extends Seeder
{
    public function run()
    {
        $countries = [
            ['id' => 1, 'name' => 'United Arab Emirates', 'iso_code' => 'AE'],
            ['id' => 2, 'name' => 'Saudi Arabia', 'iso_code' => 'SA'],
            ['id' => 3, 'name' => 'Egypt', 'iso_code' => 'EG'],
        ];

        DB::table('countries')->insertOrIgnore($countries);

        $cities = [
            ['country_id' => 1, 'name' => 'Dubai'],
            ['country_id' => 1, 'name' => 'Abu Dhabi'],
            ['country_id' => 2, 'name' => 'Riyadh'],
            ['country_id' => 3, 'name' => 'Cairo'],
        ];

        DB::table('cities')->insertOrIgnore($cities);
    }
}
