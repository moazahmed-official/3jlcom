<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SavedSearchesSeeder extends Seeder
{
    public function run()
    {
        // No default saved searches; create one sample for the first user if exists
        $user = DB::table('users')->first();
        if ($user) {
            DB::table('saved_searches')->insertOrIgnore([
                ['user_id' => $user->id, 'query_params' => json_encode(['brand' => 'Toyota', 'max_price' => 20000])]
            ]);
        }
    }
}
