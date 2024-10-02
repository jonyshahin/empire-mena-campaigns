<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login_admin(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $user = User::where('email', $request->email)->first();

            if ($user == null) {
                return custom_error('401', 'User does not exist, please contact admin');
            }

            if (!$user->hasRole('admin')) {
                return custom_error('422', 'User does not have admin role');
            }


            if (!Auth::attempt($request->only(['email', 'password']))) {
                return custom_error('400', 'Email & Password does not match with our record.');
            }

            $data = [
                'user' => $user,
                'token' => $user->createToken("my-app-token")->plainTextToken,
            ];

            return custom_success(200, 'User Logged In Successfully', $data);
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }

    public function login_client(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $user = User::where('email', $request->email)->first();

            if ($user == null) {
                return custom_error('401', 'User does not exist, please contact admin');
            }

            if (!$user->hasRole('client')) {
                return custom_error('422', 'User does not have client role');
            }


            if (!Auth::attempt($request->only(['email', 'password']))) {
                return custom_error('400', 'Email & Password does not match with our record.');
            }

            $company = $user->company;


            $data = [
                'user' => $user,
                'token' => $user->createToken("my-app-token")->plainTextToken,
                'company' => $company,
            ];

            return custom_success(200, 'User Logged In Successfully', $data);
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }

    public function login_promoter(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'email' => 'required|email',
                    'password' => 'required',
                    'outlet_id' => 'required|exists:outlets,id',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $user = User::where('email', $request->email)->first();

            if ($user == null) {
                return custom_error('401', 'User does not exist, please contact admin');
            }

            if (!$user->hasRole('promoter')) {
                return custom_error('422', 'User does not have promoter role');
            }


            if (!Auth::attempt($request->only(['email', 'password']))) {
                return custom_error('400', 'Email & Password does not match with our record.');
            }

            $data = [
                'user' => $user,
                'token' => $user->createToken("my-app-token")->plainTextToken,
            ];

            //check in time
            $attendance = new AttendanceRecord();
            $attendance->user_id = $user->id;
            $attendance->check_in_time = now();
            $attendance->outlet_id = $request->outlet_id;
            $attendance->save();

            return custom_success(200, 'User Logged In Successfully', $data);
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }

    public function login_team_leader(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $user = User::where('email', $request->email)->first();

            if ($user == null) {
                return custom_error('401', 'User does not exist, please contact admin');
            }

            if (!$user->hasRole('team_leader')) {
                return custom_error('422', 'User does not have team leader role');
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return custom_error('400', 'Email & Password does not match with our record.');
            }

            $data = [
                'user' => $user,
                'token' => $user->createToken("my-app-token")->plainTextToken,
            ];

            return custom_success(200, 'Team Leader Logged In Successfully', $data);
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }

    public function setCampaign(Request $request)
    {
        try {
            $user = Auth::user();
            $user = User::find($user->id);
            if ($user == null) {
                return custom_error(404, 'User not found');
            }

            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'campaign_id' => 'required|exists:campaigns,id',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            // Fetch the latest attendance record without a check-out time
            $attendance = AttendanceRecord::query()
                ->where('user_id', $user->id)
                ->where('check_out_time', null)
                ->latest()
                ->first();

            $attendance->campaign_id = $request->campaign_id;
            $attendance->save();

            $fetch_data = [
                'user' => $user,
                'attendance' => $attendance,
                'campaign' => $attendance->campaign,
            ];


            return custom_success(200, 'User Selected Camapign Successfully', $fetch_data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** Logout Function */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            $user = User::find($user->id);
            if ($user == null) {
                return custom_error(404, 'User not found');
            }

            if ($user->hasRole('promoter')) {

                // Fetch the latest attendance record without a check-out time
                $attendance = AttendanceRecord::query()
                    ->where('user_id', $user->id)
                    ->where('check_out_time', null)
                    ->latest()
                    ->first();

                $attendance->check_out_time = now();
                $attendance->last_day_note = $request->input('last_day_note', ' ');
                $attendance->save();

                $fetch_data = [
                    'attendance' => $attendance,
                ];
            } else {
                $fetch_data = [
                    'user' => $user,
                ];
            }

            $user->tokens()->delete();

            return custom_success(200, 'User Logged out Successfully', $fetch_data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function verifyToken()
    {
        try {
            $user = Auth::user();
            $user = User::find($user->id);

            if ($user == null) {
                return custom_error(401, 'User not found');
            }

            return custom_success(200, 'User Verified', $user);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function get_profile()
    {
        try {
            $user = Auth::user();
            return custom_success(200, 'User Profile', $user);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update_profile(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'nullable|string',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $user = User::find(Auth::user()->id);

            $user->name = $request->input('name', $user->name);
            $user->save();

            return custom_success(200, 'User Logged In Successfully', $user);
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }

    public function change_password(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'current_password' => 'required',
                    'new_password' => 'required|string|min:8|confirmed',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            // Check if the current password matches
            if (!Hash::check($request->current_password, $request->user()->password)) {
                return custom_error(400, 'Current password is incorrect');
            }

            // Update the user's password
            $request->user()->update([
                'password' => Hash::make($request->new_password),
            ]);

            return custom_success(200, 'Password successfully changed', $request->user());
        } catch (\Throwable $th) {
            return custom_error('500', $th->getMessage());
        }
    }
}
