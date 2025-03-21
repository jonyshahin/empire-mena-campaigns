<?php

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConsumersByPromoterExport implements FromQuery, WithHeadings, WithMapping, ShouldQueue, WithChunkReading
{
    use Exportable;

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
    public function query()
    {
        return Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
            ->when($this->campaign_id, fn($query) => $query->where('campaign_id', $this->campaign_id))
            ->when($this->competitor_product_ids, fn($query) => $query->whereIn('competitor_product_id', $this->competitor_product_ids))
            ->when($this->start_date, fn($query) => $query->whereDate('created_at', '>=', $this->start_date))
            ->when($this->end_date, fn($query) => $query->whereDate('created_at', '<=', $this->end_date))
            ->when($this->district_ids, fn($query) => $query->whereHas('outlet', fn($query) => $query->whereIn('district_id', $this->district_ids)))
            ->when($this->promoter_id, fn($query) => $query->where('user_id', $this->promoter_id));
    }

    public function map($consumer): array
    {
        Log::info('Exporting consumer ID: ' . $consumer->id); // ✅ This is the log
        return [
            $consumer->promoter->name ?? 'N/A',
            $consumer->outlet->name ?? 'N/A',
            $consumer->outlet->code ?? 'N/A',
            $consumer->outlet->zone->name ?? 'N/A',
            $consumer->outlet->channel ?? 'N/A',
            $consumer->outlet->district->name ?? 'N/A',
            $consumer->name ?? 'N/A',
            $consumer->telephone ?? 'N/A',
            $consumer->gender ?? 'N/A',
            $consumer->age ?? 'N/A',
            $consumer->nationality->name ?? 'N/A',
            $consumer->packs ?? 0,
            $consumer->incentives ?? 0,
            $consumer->franchise ? 'Yes' : 'No',
            $consumer->did_he_switch ? 'Yes' : 'No',
            optional(optional($consumer->competitor_product)->brand)->name ?? 'N/A',
            optional($consumer->competitor_product)->name ?? 'N/A',
            $consumer->selected_products ?? [],
            $consumer->refusedReasons->map(fn($reason) => $reason->name . ': ' . ($reason->pivot->other_refused_reason ?? ''))->implode('; '),
            $consumer->created_at->toDateTimeString(),
            $consumer->updated_at->toDateTimeString(),
        ];
    }

    public function headings(): array
    {
        return [
            'Promoter',
            'Outlet',
            'Outlet Code',
            'Zone',
            'Channel',
            'District',
            'Consumer Name',
            'Telephone',
            'Gender',
            'Age',
            'Nationality',
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

    public function chunkSize(): int
    {
        return 1000;
    }
}
