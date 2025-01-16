<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function stockCampaignReport(Request $request)
    {
        try {
            // Validate the date parameter
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_id' => 'nullable|integer|exists:districts,id',
                'outlet_id' => 'nullable|integer|exists:outlets,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            // Get the date from the request
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $districtId = $request->input('district_id');
            $outletId = $request->input('outlet_id');
            $campaign_id = $request->input('campaign_id');

            $campaign_attendance_records = AttendanceRecord::where('campaign_id', $campaign_id)
                ->with(['user'])
                ->get()
                ->groupBy('user.name');

            return custom_success(200, 'Report generated successfully', $campaign_attendance_records);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
