<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
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

            $timezone = 'Asia/Baghdad';

            $campaign_attendance_records = AttendanceRecord::where('campaign_id', $campaign_id)
                ->with([
                    'user',
                    'outlet'
                ])
                ->get()
                ->groupBy('user.name');

            $reportData = $campaign_attendance_records->map(function ($attendance_records, $promoterName) use ($timezone) {
                return [
                    'promoter' => $promoterName,
                    'attendance_records' => $attendance_records->map(function ($attendance_record) use ($timezone) {
                        return [
                            'outlet' => $attendance_record->outlet->name,
                            'check_in_time' => $attendance_record->check_in_time,
                            'check_out_time' => $attendance_record->check_out_time,
                            'last_day_note' => $attendance_record->last_day_note,
                            'stock_first' => $attendance_record->stock_first->array_map(function ($stock_first_record) {
                                return [
                                    'product_name' => $stock_first_record->product_name,
                                    'stock' => $stock_first_record->stock,
                                ];
                            }),
                            'stock_last' => $attendance_record->stock_last->array_map(function ($stock_last_record) {
                                return [
                                    'product_name' => $stock_last_record->product_name,
                                    'stock' => $stock_last_record->stock,
                                ];
                            }),
                            'created_at' => Carbon::parse($attendance_record->created_at)->timezone($timezone)->toDateTimeString(),
                            'updated_at' => Carbon::parse($attendance_record->updated_at)->timezone($timezone)->toDateTimeString(),
                        ];
                    }),
                ];
            });

            return custom_success(200, 'Report generated successfully', $reportData);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
