<?php

namespace App\Imports;

use App\Models\Consumer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ConsumersImport implements ToCollection, WithHeadingRow
{
    protected int $rowCurrent = 1;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        ++$this->rowCurrent;

        foreach ($rows as $row) {
            $user_id = $row['user_id'];
            $outlet_id = $row['outlet_id'];
            $campaign_id = $row['campaign_id'];
            $packs = $row['packs'] ?? 0;
            $selected_products = json_decode($row['selected_products'], true) ?? [];

            $consumer = Consumer::create([
                'user_id' => $user_id,
                'outlet_id' => $outlet_id,
                'name' => $row['name'],
                'telephone' => $row['telephone'],
                'competitor_brand_id' => $row['competitor_brand_id'] ?? null,
                'other_brand_name' => $row['other_brand_name'] ?? null,
                'franchise' => $row['franchise'] ?? 0,
                'did_he_switch' => $row['did_he_switch'] ?? 0,
                'aspen' => null,
                'packs' => $packs,
                'incentives' => $row['incentives'] ?? null,
                'age' => $row['age'],
                'nationality_id' => $row['nationality_id'],
                'gender' => $row['gender'],
                'campaign_id' => $campaign_id,
                'created_at' => $this->parseDate($row['created_at']),
                'updated_at' => $this->parseDate($row['updated_at']),
                'selected_products' => $selected_products,
                'competitor_product_id' => $row['competitor_product_id'],
                'dynamic_incentives' => json_decode($row['dynamic_incentives'], true),
            ]);
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null; // Return null if date is empty
        }

        try {
            // Try to parse the date using Carbon
            return Carbon::parse($date)->format('Y-m-d'); // Convert to standard 'Y-m-d' format
        } catch (\Exception $e) {
            Log::error("Invalid date format in row {$this->rowCurrent}: {$date}");
            return null; // Return null if date parsing fails
        }
    }
}
