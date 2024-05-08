<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = Outlet::all();
        return custom_success(200, 'Success', $outlets);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'district_id' => 'required|integer',
                'zone_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'code' => 'string|max:255',
                'address' => 'string|max:255',
            ]);

            $outlet = Outlet::create([
                'district_id' => $request->district_id,
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'code' => $request->code,
                'channel' => $request->channel,
                'address' => $request->address,
            ]);

            return custom_success(200, 'Outlet created successfully', $outlet);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'outlet_id' => 'required|integer',
            ]);

            $outlet = Outlet::find($request->outlet_id);

            if (!$outlet) {
                return custom_error(404, 'Outlet not found');
            }

            return custom_success(200, 'Outlet retrieved Successfuly', $outlet);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'outlet_id' => 'required|integer',
                'district_id' => 'required|integer',
                'zone_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'code' => 'string|max:255',
                'address' => 'string|max:255',
            ]);
            $outlet = Outlet::find($request->outlet_id);
            if (!$outlet) {
                return custom_error(404, 'Outlet not found');
            }
            $outlet->update([
                'district_id' => $request->input('district_id', $outlet->district_id),
                'zone_id' => $request->input('zone_id', $outlet->zone_id),
                'name' => $request->input('name', $outlet->name),
                'code' => $request->input('code', $outlet->code),
                'channel' => $request->input('channel', $outlet->channel),
                'address' => $request->input('address', $outlet->address),
            ]);

            return custom_success(200, 'Outlet updated successfully', $outlet);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'outlet_id' => 'required|integer',
            ]);

            $outlet = Outlet::find($request->outlet_id);

            if (!$outlet) {
                return custom_error(404, 'Outlet not found');
            }

            $outlet->delete();

            return custom_success(200, 'Outlet deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
