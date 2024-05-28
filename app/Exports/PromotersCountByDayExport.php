<?php

namespace App\Exports;

use App\Models\Consumer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PromotersCountByDayExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $districtId;

    public function __construct($startDate = null, $endDate = null, $districtId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->districtId = $districtId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $districtId = $this->districtId;

        // Retrieve consumers grouped by day
        $consumersQuery = Consumer::with(['promoter', 'outlet.district'])
            ->when($districtId, function ($query) use ($districtId) {
                return $query->whereHas('outlet', function ($query) use ($districtId) {
                    $query->where('district_id', $districtId);
                });
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('Y-m-d');
            });

        $data = new Collection();

        foreach ($consumersQuery as $day => $consumers) {
            $uniquePromoters = $consumers->pluck('user_id')->unique()->count();
            $numberOfVisits = $consumers->count();
            $totalContacts = $numberOfVisits; // Same as number of visits
            $averageContactsPerPromoter = $uniquePromoters > 0 ? $totalContacts / $uniquePromoters : 0;
            $contactEfficiency = ($numberOfVisits * 15) > 0 ? ($totalContacts / ($numberOfVisits * 15)) * 100 : 0;
            $switchersCount = $consumers->where('did_he_switch', true)->count();
            $franchiseCount = $consumers->where('franchise', true)->count();
            $totalEffectiveContacts = $switchersCount + $franchiseCount;
            $averageEffectiveContactsPerPromoter = $uniquePromoters > 0 ? $totalEffectiveContacts / $uniquePromoters : 0;
            $effectiveContactsEfficiency = ($numberOfVisits * 12) > 0 ? ($totalEffectiveContacts / ($numberOfVisits * 12)) * 100 : 0;
            $trialRate = $totalContacts > 0 ? ($totalEffectiveContacts / $totalContacts) * 100 : 0;
            $districtName = $consumers->first()->outlet->district->name ?? 'N/A';

            $data->push((object) [
                'day' => $day,
                'district_name' => $districtName,
                'promoter_count' => $uniquePromoters,
                'visit_count' => $numberOfVisits,
                'total_contacts' => $totalContacts,
                'average_contacts_per_promoter' => $averageContactsPerPromoter,
                'contact_efficiency' => number_format($contactEfficiency, 2) . '%',
                'switchers_count' => $switchersCount,
                'franchise_count' => $franchiseCount,
                'total_effective_contacts' => $totalEffectiveContacts,
                'average_effective_contacts_per_promoter' => $averageEffectiveContactsPerPromoter,
                'effective_contacts_efficiency' => number_format($effectiveContactsEfficiency, 2) . '%',
                'trial_rate' => number_format($trialRate, 2) . '%',
            ]);
        }

        return $data;
    }

    public function map($row): array
    {
        return [
            $row->day,
            $row->district_name,
            $row->promoter_count,
            $row->visit_count,
            $row->total_contacts,
            $row->average_contacts_per_promoter,
            $row->contact_efficiency,
            $row->switchers_count,
            $row->franchise_count,
            $row->total_effective_contacts,
            $row->average_effective_contacts_per_promoter,
            $row->effective_contacts_efficiency,
            $row->trial_rate,
        ];
    }

    public function headings(): array
    {
        return [
            'Day',
            'District Name',
            'Promoter Count',
            'Visit Count',
            'Total Contacts',
            'Average Contacts per Promoter',
            'Contact Efficiency',
            'Switchers Count',
            'Franchise Count',
            'Total Effective Contacts',
            'Average Effective Contacts per Promoter',
            'Effective Contacts Efficiency',
            'Trial Rate',
        ];
    }
}
