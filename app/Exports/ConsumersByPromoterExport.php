<?php

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersByPromoterExport implements FromCollection, WithHeadings
{
    protected $start_date;
    protected $end_date;
    protected $district_ids;
    protected $competitor_brand_id;
    protected $promoter_id;
    protected $campaign_id;


    public function __construct($start_date = null, $end_date = null, $district_ids = null, $competitorBrandId = null, $promoterId = null, $campaign_id = null)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->district_ids = $district_ids;
        $this->competitor_brand_id = $competitorBrandId;
        $this->promoter_id = $promoterId;
        $this->campaign_id = $campaign_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $start_date = $this->start_date;
        $end_date = $this->end_date;
        $districtIds = $this->district_ids;
        $competitorBrandId = $this->competitor_brand_id;
        $promoterId = $this->promoter_id;
        $campaign_id = $this->campaign_id;


        // Retrieve consumers grouped by promoter
        $consumersQuery = Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
            ->when($campaign_id, function ($query, $campaign_id) {
                return $query->where('campaign_id', $campaign_id);
            })
            ->when($start_date, function ($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($end_date, function ($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->when($districtIds, function ($query, $districtIds) {
                return $query->whereHas('outlet', function ($query) use ($districtIds) {
                    $query->whereIn('district_id', $districtIds);
                });
            })
            ->when($competitorBrandId, function ($query, $competitorBrandId) {
                return $query->where('competitor_brand_id', $competitorBrandId);
            })
            ->when($promoterId, function ($query, $promoterId) {
                return $query->where('user_id', $promoterId);
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
