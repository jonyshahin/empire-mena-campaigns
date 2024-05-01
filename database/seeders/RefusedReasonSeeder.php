<?php

namespace Database\Seeders;

use App\Models\RefusedReason;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RefusedReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RefusedReason::create(['name' => 'Loyalty', 'type' => 1]);
        RefusedReason::create(['name' => 'Price', 'type' => 1]);
        RefusedReason::create(['name' => 'Taste', 'type' => 1]);
        RefusedReason::create(['name' => 'Others', 'type' => 2]);
    }
}
