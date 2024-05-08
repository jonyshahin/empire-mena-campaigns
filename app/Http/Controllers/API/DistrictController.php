<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index()
    {
        $districts = District::all();
        return custom_success(200, 'Success', $districts);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $district = District::create([
                'name' => $request->name,
            ]);

            return custom_success(200, 'District created successfully', $district);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'district_id' => 'required|integer',
            ]);

            $district = District::find($request->district_id);

            if (!$district) {
                return custom_error(404, 'District not found');
            }

            return custom_success(200, 'District retrieved Successfuly', $district);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'district_id' => 'required|integer',
                'name' => 'required|string|max:255',
            ]);
            $district = District::find($request->district_id);
            if (!$district) {
                return custom_error(404, 'District not found');
            }
            $district->update([
                'name' => $request->input('name', $district->name),
            ]);

            return custom_success(200, 'District updated successfully', $district);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'district_id' => 'required|integer',
            ]);

            $district = District::find($request->district_id);

            if (!$district) {
                return custom_error(404, 'District not found');
            }

            $district->delete();

            return custom_success(200, 'District deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
