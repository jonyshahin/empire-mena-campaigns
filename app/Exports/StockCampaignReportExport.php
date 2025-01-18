<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockCampaignReportExport implements FromCollection, WithHeadings
{
    protected $start_date;
    protected $end_date;
    protected $district_ids;
    protected $outlet_ids;
    protected $promoter_ids;
    protected $campaign_id;

    public function __construct($start_date = null, $end_date = null, $district_ids = null, $outlet_ids = null, $promoter_ids = null, $campaign_id = null)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->district_ids = $district_ids;
        $this->promoter_ids = $promoter_ids;
        $this->campaign_id = $campaign_id;
        $this->outlet_ids = $outlet_ids;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $start_date = $this->start_date;
        $end_date = $this->end_date;
        $districtIds = $this->district_ids;
        $promoterIds = $this->promoter_ids;
        $campaign_id = $this->campaign_id;
        $outletIds = $this->outlet_ids;

        $timezone = 'Asia/Baghdad';

        $campaign_attendance_records = AttendanceRecord::where('campaign_id', $campaign_id)
            ->with([
                'user',
                'outlet'
            ])
            ->get()
            ->groupBy('user.name');

        $data = new Collection();
        if ($campaign_attendance_records) {
            foreach ($campaign_attendance_records as $promoterName => $attendance_records) {
                foreach ($attendance_records as $attendance_record) {
                    $stocks = [];

                    // Process stock_first
                    if ($attendance_record->stock_first) {
                        foreach ($attendance_record->stock_first as $stock_first_record) {
                            $product_name = $stock_first_record['product_name'];
                            $stocks[$product_name] = [
                                'product_name' => $product_name,
                                'stock_in' => $stock_first_record['stock'],
                                'stock_out' => NULL, // Initialize stock_out
                            ];
                        }
                    }

                    // Process stock_last
                    if ($attendance_record->stock_last) {
                        foreach ($attendance_record->stock_last as $stock_last_record) {
                            $product_name = $stock_last_record['product_name'];
                            if (isset($stocks[$product_name])) {
                                $stocks[$product_name]['stock_out'] = $stock_last_record['stock'];
                            } else {
                                $stocks[$product_name] = [
                                    'product_name' => $product_name,
                                    'stock_in' => NULL, // Initialize stock_in
                                    'stock_out' => $stock_last_record['stock'],
                                ];
                            }
                        }
                    }

                    foreach ($stocks as $stock) {
                        $outlet = $attendance_record->outlet;
                        $outlet_name = NULL;
                        $district_name = NULL;
                        $zone_name = NULL;
                        if ($outlet) {
                            $outlet_name = $attendance_record->outlet->name;
                            $district = $attendance_record->outlet->district;

                            if ($district) {
                                $district_name = $attendance_record->outlet->district->name;
                            }
                            $zone = $attendance_record->outlet->zone;

                            if ($zone) {
                                $zone_name = $attendance_record->outlet->zone->name;
                            }
                        }
                        $data->push([
                            'Promoter' => $promoterName,
                            'District' => $district_name,
                            'Zone' => $zone_name,
                            'Outlet' => $outlet_name,
                            'Check In Time' => $attendance_record->check_in_time,
                            'Check Out Time' => $attendance_record->check_out_time,
                            'Last Day Note' => $attendance_record->last_day_note,
                            'Product Name' => $stock['product_name'],
                            'Stock In' => $stock['stock_in'],
                            'Stock Out' => $stock['stock_out'],
                        ]);
                    }
                }
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Promoter',
            'District',
            'Zone',
            'Outlet',
            'Check In Time',
            'Check Out Time',
            'Last Day Note',
            'Product Name',
            'Stock In',
            'Stock Out',
        ];
    }
}
