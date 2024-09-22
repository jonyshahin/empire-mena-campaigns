<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class TeamLeaderController extends Controller
{
    public function index()
    {
        $team_leaders = User::role('team_leader')->get();
        return custom_success(200, 'Team Leaders', $team_leaders);
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

        $role = Role::findByName('team_leader');
        $user->assignRole($role);

        return custom_success(200, 'Team Leader created successfully!', $user);
    }

    public function show(Request $request)
    {
        try {
            $validated = $request->validate([
                'team_leader_id' => 'required|integer',
            ]);

            $user = User::find($request->team_leader_id);
            if (!$user) {
                return custom_error(404, 'Team Leader not found');
            }

            return custom_success(200, 'Team Leader', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'team_leader_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $request->promoter_id,
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            $user = User::query()->where('id', $request->team_leader_id)->role('team_leader')->first();
            if (!$user) {
                return custom_error(404, 'Team Leader not found');
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($validated['password']) {
                $user->password = bcrypt($validated['password']);
            }

            $user->save();

            return custom_success(200, 'Team Leader updated successfully!', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'team_leader_id' => 'required|integer|exists:users,id',
            ]);

            $user = User::query()->where('id', $request->team_leader_id)->role('team_leader')->first();
            if (!$user) {
                return custom_error(404, 'Team Leader not found');
            }

            $user->delete();

            return custom_success(200, 'Team Leader deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
