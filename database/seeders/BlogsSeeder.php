<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogsSeeder extends Seeder
{
    public function run()
    {
        DB::table('blogs')->insertOrIgnore([
            ['title' => 'Welcome', 'body' => 'Welcome to our platform', 'status' => 'published']
        ]);
    }
}
