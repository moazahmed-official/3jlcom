<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecificationsSeeder extends Seeder
{
    public function run()
    {
        DB::table('specifications')->insertOrIgnore([
            ['name_en' => 'Mileage', 'name_ar' => 'المسافة', 'type' => 'number', 'values' => null],
            ['name_en' => 'Transmission', 'name_ar' => 'ناقل الحركة', 'type' => 'select', 'values' => json_encode(['Automatic','Manual'])]
        ]);
    }
}
