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
        Outlet::create(['name' => 'Outlet 1', 'code' => '123456', 'address' => '123 Main St']);
        Outlet::create(['name' => 'Outlet 2', 'code' => '654321', 'address' => '456 Elm St']);
        Outlet::create(['name' => 'Outlet 3', 'code' => '987654', 'address' => '789 Oak St']);
    }
}
