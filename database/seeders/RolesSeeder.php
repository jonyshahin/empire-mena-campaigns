<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'promoter', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
    }
}
