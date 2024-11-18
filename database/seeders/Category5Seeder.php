<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class Category5Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert data
        DB::table('category5s')->insert([
            [
                'name' => 'ECONSENT',
                'description' => 'E-Consent',
            ],
            [
                'name' => 'MAILCONV',
                'description' => 'Mail Consent',
            ],
            [
                'name' => 'NOCONSENT',
                'description' => 'No Consent',
            ],
        ]);
    }
}
