<?php

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
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

        $data = new Collection();

        foreach ($consumersQuery as $promoterName => $consumers) {
            foreach ($consumers as $consumer) {
                $data->push([
                    'Promoter' => $promoterName,
                    'Outlet' => $consumer->outlet->name,
                    'District' => $consumer->outlet->district->name,
                    'Consumer Name' => $consumer->name,
                    'Packs' => $consumer->packs,
                    'Incentives' => $consumer->incentives,
                    'Franchise' => $consumer->franchise ? 'Yes' : 'No',
                    'Did He Switch' => $consumer->did_he_switch ? 'Yes' : 'No',
                    'Created At' => $consumer->created_at->toDateTimeString(),
                    'Updated At' => $consumer->updated_at->toDateTimeString(),
                ]);
            }
        }

        return $data;
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
