<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use Illuminate\Http\Request;

class NationalityController extends Controller
{
    public function index()
    {
        $nationalities = Nationality::all();
        return custom_success(200, 'Success', $nationalities);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $nationality = Nationality::create([
                'name' => $request->name,
            ]);

            return custom_success(200, 'Nationality created successfully', $nationality);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'nationality_id' => 'required|integer',
            ]);

            $nationality = Nationality::find($request->nationality_id);

            if (!$nationality) {
                return custom_error(404, 'Nationality not found');
            }

            return custom_success(200, 'Nationality retrieved Successfuly', $nationality);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'nationality_id' => 'required|integer',
                'name' => 'required|string|max:255',
            ]);
            $nationality = Nationality::find($request->nationality_id);
            if (!$nationality) {
                return custom_error(404, 'Nationality not found');
            }
            $nationality->update([
                'name' => $request->input('name', $nationality->name),
            ]);

            return custom_success(200, 'Nationality updated successfully', $nationality);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'nationality_id' => 'required|integer',
            ]);

            $nationality = Nationality::find($request->nationality_id);

            if (!$nationality) {
                return custom_error(404, 'Nationality not found');
            }

            $nationality->delete();

            return custom_success(200, 'Nationality deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
