<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Mena Empire Super Admin',
            'email' => 'super.admin@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');
    }
}
