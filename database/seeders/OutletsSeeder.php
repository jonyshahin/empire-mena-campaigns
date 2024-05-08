<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OutletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlet::create(['district_id' => 1, 'zone_id' => 1, 'name' => 'Outlet 1', 'code' => '123456', 'address' => '123 Main St']);
        Outlet::create(['district_id' => 2, 'zone_id' => 2, 'name' => 'Outlet 2', 'code' => '654321', 'address' => '456 Elm St']);
        Outlet::create(['district_id' => 3, 'zone_id' => 3, 'name' => 'Outlet 3', 'code' => '987654', 'address' => '789 Oak St']);
    }
}
