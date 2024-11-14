<?php

namespace App\Exports;

use App\Models\Consumer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersByPromoterExport implements FromCollection, WithHeadings
{
    protected $start_date;
    protected $end_date;
    protected $district_ids;
    protected $competitor_product_ids;
    protected $promoter_id;
    protected $campaign_id;


    public function __construct($start_date = null, $end_date = null, $district_ids = null, $promoter_id = null, $campaign_id = null, $competitor_product_ids = null)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->district_ids = $district_ids;
        $this->competitor_product_ids = $competitor_product_ids;
        $this->promoter_id = $promoter_id;
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
        $promoterId = $this->promoter_id;
        $campaign_id = $this->campaign_id;
        $competitor_product_ids = $this->competitor_product_ids;

        // Retrieve consumers grouped by promoter
        $consumersQuery = Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
            ->when($campaign_id, function ($query, $campaign_id) {
                return $query->where('campaign_id', $campaign_id);
            })
            ->when($competitor_product_ids, function ($query, $competitor_product_ids) {
                return $query->whereIn('competitor_product_id', $competitor_product_ids);
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
                    'Outlet Code' => $consumer->outlet->code,
                    'Channel' => $consumer->outlet->channel,
                    'District' => $consumer->outlet->district->name,
                    'Consumer Name' => $consumer->name,
                    'Telephone' => $consumer->telephone,
                    'Gender' => $consumer->gender,
                    'Age' => $consumer->age,
                    'Packs' => $consumer->packs,
                    'Incentives' => $consumer->incentives,
                    'Franchise' => $consumer->franchise ? 'Yes' : 'No',
                    'Did He Switch' => $consumer->did_he_switch ? 'Yes' : 'No',
                    'Competitor Brand' => optional($consumer->competitor_product)->brand->name,
                    'Competitor Product' => optional($consumer->competitor_product)->name,
                    // 'Other Brand Name' => $consumer->other_brand_name == null ? '' : $consumer->other_brand_name,
                    'Products' => $consumer->selected_products,
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
            'Outlet Code',
            'Channel',
            'District',
            'Consumer Name',
            'Telephone',
            'Gender',
            'Age',
            'Packs',
            'Incentives',
            'Franchise',
            'Did He Switch',
            'Competitor Brand',
            'Competitor Product',
            'Products',
            'Refusal Reasons',
            'Created At',
            'Updated At',
        ];
    }
}
