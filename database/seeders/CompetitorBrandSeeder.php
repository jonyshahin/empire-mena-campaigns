<?php

namespace Database\Seeders;

use App\Models\CompetitorBrand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompetitorBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompetitorBrand::create(['name' => 'Marllboro',]);
        CompetitorBrand::create(['name' => 'Kent',]);
        CompetitorBrand::create(['name' => 'Esse',]);
    }
}
