<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryCitySeeder extends Seeder
{
    public function run()
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Egypt',
            'code' => 'EG',
            'created_at' => now(),
        ]);

        DB::table('cities')->insert([
            ['country_id' => $countryId, 'name' => 'Cairo', 'created_at' => now()],
            ['country_id' => $countryId, 'name' => 'Alexandria', 'created_at' => now()],
        ]);
    }
}
