<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class PromoterController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::user()->id);
        if ($user->hasRole('team_leader')) {
            $campaign_id = $user->attendanceRecords()->latest()->first()->campaign_id;
            $promoters = Campaign::find($campaign_id)->promoters;
            return custom_success(200, 'Promoters', $promoters);
        }
        $promoters = User::query()->role('promoter')->get();
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

    public function show(Request $request)
    {
        try {
            $validated = $request->validate([
                'promoter_id' => 'required|integer',
            ]);

            $user = User::find($request->promoter_id);
            if (!$user) {
                return custom_error(404, 'Promoter not found');
            }

            return custom_success(200, 'Promoter', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'promoter_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $request->promoter_id,
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            $user = User::query()->where('id', $request->promoter_id)->role('promoter')->first();
            if (!$user) {
                return custom_error(404, 'Promoter not found');
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($validated['password']) {
                $user->password = bcrypt($validated['password']);
            }

            $user->save();

            return custom_success(200, 'Promoter updated successfully!', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }
}
