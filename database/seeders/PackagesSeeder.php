<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagesSeeder extends Seeder
{
    public function run()
    {
        $packages = [
            ['name' => 'Basic', 'description' => 'Basic listing package', 'price' => 0.00, 'duration_days' => 30, 'features' => json_encode(['listings' => 10])],
            ['name' => 'Pro', 'description' => 'Pro package with more listings', 'price' => 49.99, 'duration_days' => 30, 'features' => json_encode(['listings' => 100])],
        ];

        DB::table('packages')->insertOrIgnore($packages);
    }
}
