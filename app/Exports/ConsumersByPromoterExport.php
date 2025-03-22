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

    protected int $rowCount = 0;
    protected static ?int $chunkStartId = null;
    protected static ?int $chunkEndId = null;
    protected $start_date;
    protected $end_date;
    protected $district_ids;
    protected $competitor_product_ids;
    protected $promoter_id;
    protected $campaign_id;
    protected $totalCount;
    protected $exportKey;


    public function __construct($start_date = null, $end_date = null, $district_ids = null, $promoter_id = null, $campaign_id = null, $competitor_product_ids = null, $totalCount = 0)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->district_ids = $district_ids;
        $this->competitor_product_ids = $competitor_product_ids;
        $this->promoter_id = $promoter_id;
        $this->campaign_id = $campaign_id;
        $this->totalCount = $totalCount;
        $this->exportKey = 'export_progress_' . now()->timestamp . '_' . uniqid();
        cache()->put($this->exportKey . '_row_count', 0);
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
        // self::$rowCount++;
        $rowCount = cache()->increment($this->exportKey . '_row_count');

        // ✅ Log total rows once at the beginning
        if ($rowCount === 1) {
            Log::info('📦 Export progress: ' . $this->totalCount . ' total rows will be processed.');
        }

        // Capture first ID in chunk
        if (self::$chunkStartId === null) {
            self::$chunkStartId = $consumer->id;
        }

        // Keep updating the end ID
        self::$chunkEndId = $consumer->id;

        // Log progress at every 1000th row
        if ($rowCount % $this->chunkSize() === 0 || $rowCount === $this->totalCount) {
            $percent = $this->totalCount > 0
                ? number_format(($rowCount / $this->totalCount) * 100, 2)
                : 0;

            Log::info("⏱ Export progress: " . $rowCount . " rows processed (~{$percent}%)");
            Log::info("✅ Chunk processed: IDs " . self::$chunkStartId . ' to ' . self::$chunkEndId);

            // Reset for next chunk
            self::$chunkStartId = null;
            self::$chunkEndId = null;
        }

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
        return 500;
    }
}
