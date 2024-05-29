<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
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

    /** Logout Function */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            $user = User::find($user->id);
            if ($user == null) {
                return custom_error(404, 'User not found');
            }

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
}
