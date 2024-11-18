<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class Category1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'EPLWAIVFEE',
            'ERCS',
            'GMUDSTAFF',
            'GMUDSTU',
            'GMUSTAFF',
            'GMUSTUDENT',
            'KINGSACADM',
            'KINGSSTAFF',
            'KINGSSTDNT',
            'LIBGUIDE',
            'NQSTAFF',
            'NQSTAFFOC',
            'NQSTDNT',
            'NQSTUDOC',
            'ONRES SET',
            'STAFF',
            'IJANEW',
            'UARNEW',
            'UASTAFF',
            'UASTUDENT',
        ];

        foreach ($categories as $category) {
            DB::table('category1s')->insert([
                'name' => $category,
                'description' => 'Description for ' . $category,  // Optional: Add a description
            ]);
        }
    }
}
