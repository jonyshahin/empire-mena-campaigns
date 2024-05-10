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
        CompetitorBrand::create(['name' => 'Esse Change/Black',]);
        CompetitorBrand::create(['name' => 'Kent Capsule',]);
        CompetitorBrand::create(['name' => 'MBO Vista',]);
        CompetitorBrand::create(['name' => 'Pine',]);
        CompetitorBrand::create(['name' => 'Oscar Capsule',]);
        CompetitorBrand::create(['name' => 'Mac Capsule',]);
        CompetitorBrand::create(['name' => 'Esse Silver',]);
        CompetitorBrand::create(['name' => 'Milano',]);
        CompetitorBrand::create(['name' => 'MT',]);
        CompetitorBrand::create(['name' => 'Master',]);
        CompetitorBrand::create(['name' => 'Other',]);
    }
}
