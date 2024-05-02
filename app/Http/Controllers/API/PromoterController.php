<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class PromoterController extends Controller
{
    public function index()
    {
        $promoters = User::role('promoter')->get();
        return custom_success(200, 'Promoters', $promoters);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $role = Role::findByName('promoter');
        $user->assignRole($role);

        return custom_success(200, 'Promoter created successfully!', $user);
    }
}
