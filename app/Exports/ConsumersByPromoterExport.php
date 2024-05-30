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
    protected $competitor_brand_id;

    public function __construct($date = null, $district_id = null, $competitorBrandId = null)
    {
        $this->date = $date;
        $this->district_id = $district_id;
        $this->competitor_brand_id = $competitorBrandId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $date = $this->date;
        $districtId = $this->district_id;
        $competitorBrandId = $this->competitor_brand_id;

        // Retrieve consumers grouped by promoter
        $consumersQuery = Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
            ->when($date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->when($districtId, function ($query, $districtId) {
                return $query->whereHas('outlet', function ($query) use ($districtId) {
                    $query->where('district_id', $districtId);
                });
            })
            ->when($competitorBrandId, function ($query, $competitorBrandId) {
                return $query->where('competitor_brand_id', $competitorBrandId);
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
                    'Competitor Brand' => optional($consumer->competitorBrand)->name,
                    'Other Brand Name' => $consumer->other_brand_name == null ? '' : $consumer->other_brand_name,
                    'Aspen' => $consumer->aspen,
                    'Refusal Reasons' => $consumer->refusedReasons->map(function ($reason) {
                        return [
                            'reason' => $reason->name,
                            'other_reason' => $reason->pivot->other_refused_reason,
                        ];
                    }),
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
            'Competitor Brand',
            'Other Brand Name',
            'Aspen',
            'Refusal Reasons',
            'Created At',
            'Updated At',
        ];
    }
}
