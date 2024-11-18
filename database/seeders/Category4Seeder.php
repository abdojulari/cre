<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Category4Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert data
        DB::table('category4s')->insert([
            [
                'name' => 'CELA',
                'description' => 'CELA',
            ],
            [
                'name' => 'CELA-NNEL',
                'description' => 'CELA-NNEL',
            ],
            [
                'name' => 'NNELS',
                'description' => 'NNELS',
            ]  
        ]);
    }
}
