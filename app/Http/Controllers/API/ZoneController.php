<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::all();
        return custom_success(200, 'Success', $zones);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'district_id' => 'required|integer',
                'name' => 'required|string|max:255',
            ]);

            $zone = Zone::create([
                'district_id' => $request->district_id,
                'name' => $request->name,
            ]);

            return custom_success(200, 'Zone created successfully', $zone);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'zone_id' => 'required|integer',
            ]);

            $zone = Zone::find($request->zone_id);

            if (!$zone) {
                return custom_error(404, 'Zone not found');
            }

            return custom_success(200, 'Zone retrieved Successfuly', $zone);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'zone_id' => 'required|integer',
                'district_id' => 'required|integer',
                'name' => 'required|string|max:255',
            ]);
            $zone = Zone::find($request->zone_id);
            if (!$zone) {
                return custom_error(404, 'Zone not found');
            }
            $zone->update([
                'district_id' => $request->input('district_id', $zone->district_id),
                'name' => $request->input('name', $zone->name),
            ]);

            return custom_success(200, 'Zone updated successfully', $zone);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'zone_id' => 'required|integer',
            ]);

            $zone = Zone::find($request->zone_id);

            if (!$zone) {
                return custom_error(404, 'Zone not found');
            }

            $zone->delete();

            return custom_success(200, 'Zone deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
