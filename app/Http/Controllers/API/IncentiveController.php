<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Incentive;
use Illuminate\Http\Request;

class IncentiveController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'campaign_id' => 'required|integer',
            ]);

            $incentives = Incentive::where('campaign_id', $request->campaign_id)->get();
            return custom_success(200, 'Success', $incentives);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'incentive_id' => 'required|integer',
            ]);

            $incentive = Incentive::find($request->incentive_id);

            if (!$incentive) {
                return custom_error(404, 'Incentive not found');
            }

            return custom_success(200, 'Incentive retrieved Successfuly', $incentive);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'campaign_id' => 'required|integer|exists:campaigns,id',
                'brand_id' => 'required|integer|exists:competitor_brands,id',
                'name' => 'required|string|max:255',
                'value' => 'required|integer',
            ]);

            $incentive = Incentive::create($validatedData);
            return custom_success(200, 'Incentive created successfully', $incentive);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'incentive_id' => 'required|integer',
                'campaign_id' => 'required|integer|exists:campaigns,id',
                'brand_id' => 'required|integer|exists:competitor_brands,id',
                'name' => 'required|string|max:255',
                'value' => 'required|integer',
            ]);

            $incentive = Incentive::find($request->incentive_id);
            $incentive->update($validatedData);
            return custom_success(200, 'Incentive updated successfully', $incentive);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'incentive_id' => 'required|integer',
            ]);
            $incentive = Incentive::findOrFail($request->incentive_id);
            $incentive->delete();
            return custom_success(200, 'Incentive deleted successfully', $incentive);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
