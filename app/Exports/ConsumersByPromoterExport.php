<?php

namespace App\Exports;

use App\Models\Consumer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersByPromoterExport implements FromCollection, WithHeadings
{
    protected $date;
    protected $district_id;

    public function __construct($date = null, $district_id = null)
    {
        $this->date = $date;
        $this->district_id = $district_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $date = $this->date;
        $districtId = $this->district_id;

        // Retrieve consumers grouped by promoter
        $consumersQuery = Consumer::with('promoter', 'outlet.district')
            ->when($date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->when($districtId, function ($query, $districtId) {
                return $query->whereHas('outlet', function ($query) use ($districtId) {
                    $query->where('district_id', $districtId);
                });
            })
            ->get()
            ->groupBy('promoter.name');

        return $consumersQuery;
    }

    public function headings(): array
    {
        return [
            'Promoter',
            'Outlet',
            'District',
            'Consumer Name',
            'Packs',
            'Incentives',
            'Franchise',
            'Did He Switch',
            'Created At',
            'Updated At',
        ];
    }
}
