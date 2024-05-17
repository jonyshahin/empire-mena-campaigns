<?php

namespace Database\Seeders;

use App\Models\Nationality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NationalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Nationality::create(['name' => 'Iraqi']);
        Nationality::create(['name' => 'Kurdish']);
        Nationality::create(['name' => 'Arab']);
        Nationality::create(['name' => 'Expat']);
    }
}
