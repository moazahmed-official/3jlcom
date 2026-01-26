<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlidersSeeder extends Seeder
{
    public function run()
    {
        DB::table('sliders')->insertOrIgnore([
            ['name' => 'Homepage Hero', 'value' => '/', 'status' => 'active']
        ]);
    }
}
