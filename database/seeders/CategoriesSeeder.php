<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insertOrIgnore([
            ['name_en' => 'Cars', 'name_ar' => 'سيارات', 'status' => 'active'],
            ['name_en' => 'Bikes', 'name_ar' => 'دراجات', 'status' => 'active']
        ]);
    }
}
