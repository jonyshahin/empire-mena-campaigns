<?php

namespace App\Http\Controllers\API;

use App\Exports\StockCampaignReportExport;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function stockCampaignReport(Request $request)
    {
        try {
            // Validate the date parameter
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_ids' => 'nullable|array',
                'district_ids.*' => 'integer|exists:districts,id',
                'outlet_ids' => 'nullable|array',
                'outlet_ids.*' => 'nullable|integer|exists:outlets,id',
                'promoter_ids' => 'nullable|array',
                'promoter_ids.*' => 'nullable|integer|exists:users,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            // Get the date from the request
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $district_ids = $request->input('district_ids');
            $outlet_ids = $request->input('outlet_ids');
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
                        $outlet = $attendance_record->outlet;
                        $outlet_name = NULL;
                        if ($outlet) {
                            $outlet_name = $attendance_record->outlet->name;
                            $district = $attendance_record->outlet->district;
                            $district_name = NULL;
                            if ($district) {
                                $district_name = $attendance_record->outlet->district->name;
                            }
                            $zone = $attendance_record->outlet->zone;
                            $zone_name = NULL;
                            if ($zone) {
                                $zone_name = $attendance_record->outlet->zone->name;
                            }
                        }
                        return [
                            'district' => $district_name,
                            'zone' => $zone_name,
                            'outlet' => $outlet_name,
                            'check_in_time' => $attendance_record->check_in_time,
                            'check_out_time' => $attendance_record->check_out_time,
                            'last_day_note' => $attendance_record->last_day_note,
                            'stock_first' => $attendance_record->stock_first ? array_map(function ($stock_first_record) {
                                return [
                                    'product_name' => $stock_first_record['product_name'],
                                    'stock' => $stock_first_record['stock'],
                                ];
                            }, $attendance_record->stock_first) : [],
                            'stock_last' => $attendance_record->stock_last ? array_map(function ($stock_last_record) {
                                return [
                                    'product_name' => $stock_last_record['product_name'],
                                    'stock' => $stock_last_record['stock'],
                                ];
                            }, $attendance_record->stock_last) : [],
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

    public function exportStockCampaignReport(Request $request)
    {
        // Validate the date parameter
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'district_ids' => 'nullable|array',
            'district_ids.*' => 'integer|exists:districts,id',
            'outlet_ids' => 'nullable|array',
            'outlet_ids.*' => 'nullable|integer|exists:outlets,id',
            'promoter_ids' => 'nullable|array',
            'promoter_ids.*' => 'nullable|integer|exists:users,id',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
        ]);

        // Get the date from the request
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $district_ids = $request->input('district_ids');
        $outlet_ids = $request->input('outlet_ids');
        $promoter_ids = $request->input('promoter_ids');
        $campaign_id = $request->input('campaign_id');

        return Excel::download(new StockCampaignReportExport($start_date, $end_date, $district_ids, $outlet_ids, $promoter_ids, $campaign_id), 'consumers_by_promoter_report.xlsx');
    }
}
