<?php

namespace Database\Seeders;

use App\Models\Category6;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Category6Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    { 
        // Insert data
        DB::table('category6s')->insert([
            ['name' => 'CMA_COLL',
            'description' => 'CMA Collections',
            ],
            [
                'name' => 'FLOAT',
                'description' => 'float',
            ]
        ]);
    }
}
