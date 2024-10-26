<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromoterDailyFeedback implements FromCollection, WithHeadings
{
    protected $start_date;
    protected $end_date;
    protected $district_ids;
    protected $campaign_id;

    public function __construct($start_date = null, $end_date = null, $district_ids = null, $campaign_id = null)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->district_ids = $district_ids;
        $this->campaign_id = $campaign_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $districtIds = $this->district_ids;
        $campaign_id = $this->campaign_id;

        $attendance_records = AttendanceRecord::with(['user', 'outlet', 'campaign'])
            ->when($campaign_id, function ($query, $campaign_id) {
                return $query->where('campaign_id', $campaign_id);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->when($districtIds, function ($query, $districtIds) {
                return $query->whereHas('outlet', function ($query) use ($districtIds) {
                    $query->whereIn('district_id', $districtIds);
                });
            })
            ->get()
            ->groupBy('user.name');

        $data = new Collection();

        foreach ($attendance_records as $promoterName => $attendance_records) {
            foreach ($attendance_records as $attendance_record) {
                $data->push([
                    'Promoter' => $promoterName,
                    'Campaign' => $attendance_record->campaign->name,
                    'District' => $attendance_record->outlet->district->name,
                    'Outlet' => $attendance_record->outlet->name,
                    'Check In Time' => $attendance_record->check_in_time->toDateTimeString(),
                    'Check Out Time' => isset($attendance_record->check_out_time) ? $attendance_record->check_out_time->toDateTimeString() : '',
                    'Last Day Note' => $attendance_record->last_day_note,
                    'Created At' => $attendance_record->created_at->toDateTimeString(),
                    'Updated At' => $attendance_record->updated_at->toDateTimeString(),
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Promoter',
            'Campaign',
            'District',
            'Outlet',
            'Check In Time',
            'Check Out Time',
            'Last Day Note',
            'Created At',
            'Updated At',
        ];
    }
}
