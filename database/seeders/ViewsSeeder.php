<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ViewsSeeder extends Seeder
{
    public function run()
    {
        // add a sample view record
        DB::table('views')->insertOrIgnore([
            ['target_type' => 'ad', 'target_id' => 1, 'count' => 0]
        ]);
    }
}
