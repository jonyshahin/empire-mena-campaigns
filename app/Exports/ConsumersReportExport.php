<?php

namespace App\Exports;

use App\Models\Consumer;
use App\Models\District;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersReportExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $date;
    protected $district_id;
    protected $outlet_id;
    protected $start_date;
    protected $end_date;

    public function __construct($start_date = null, $end_date = null, $district_id = null, $outlet_id = null)
    {
        $this->district_id = $district_id;
        $this->outlet_id = $outlet_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function headings(): array
    {
        return [
            'Outlet',
            '# Consumers',
            '# Effective Consumers',
            '# Packs',
            '# Incentive Lvl1',
            '# Incentive Lvl2',
            '# Franchise',
            '# Switch',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        try {
            $start_date = $this->start_date;
            $end_date = $this->end_date;
            $districtId = $this->district_id;
            $outletId = $this->outlet_id;

            // $timezone = $user ? $user->timezone : 'UTC';
            $timezone = 'Asia/Baghdad';


            // Retrieve districts based on the presence of district_id
            $districtsQuery = District::with(['outlets.consumers' => function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereDate('created_at', '>=', $start_date);
                }
                if ($end_date) {
                    $query->whereDate('created_at', '<=', $end_date);
                }
                $query->orderBy('created_at', 'desc');
            }]);

            if ($districtId) {
                $districtsQuery->where('id', $districtId);
            }

            if ($outletId) {
                $districtsQuery->whereHas('outlets', function ($query) use ($outletId) {
                    $query->where('id', $outletId);
                });
            }

            $districts = $districtsQuery->get();

            // Transform data to include time zone conversion
            $reportData = $districts->map(function ($district) use ($outletId, $timezone) {
                $outlets = $district->outlets;

                // If outlet_id is provided, filter the outlets collection to include only the specific outlet
                if ($outletId) {
                    $outlets = $outlets->filter(function ($outlet) use ($outletId) {
                        return $outlet->id == $outletId;
                    });
                }

                return [
                    'district' => $district->name,
                    'outlet_count' => $outlets->count(),
                    'total_consumers_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->count();
                    }),
                    'total_effective_consumers_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('packs', '>', 0)->count();
                    }),
                    'total_packs_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->sum('packs');
                    }),
                    'total_incentive_lvl1_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl1')->count();
                    }),
                    'total_incentive_lvl2_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl2')->count();
                    }),
                    'total_franchise_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('franchise', true)->count();
                    }),
                    'total_did_he_switch_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('did_he_switch', true)->count();
                    }),
                    'outlets' => $outlets->map(function ($outlet) use ($timezone) {
                        return [
                            'outlet' => $outlet->name,
                            'consumer_count' => $outlet->consumers->count(),
                            'effective_consumer_count' => $outlet->consumers->where('packs', '>', 0)->count(),
                            'total_packs_in_outlet' => $outlet->consumers->sum('packs'),
                            'total_incentive_lvl1_in_outlet' => $outlet->consumers->where('incentives', 'lvl1')->count(),
                            'total_incentive_lvl2_in_outlet' => $outlet->consumers->where('incentives', 'lvl2')->count(),
                            'total_franchise_in_outlet' => $outlet->consumers->where('franchise', true)->count(),
                            'total_did_he_switch_in_outlet' => $outlet->consumers->where('did_he_switch', true)->count(),
                        ];
                    }),
                ];
            });
            return $reportData[0]['outlets'];
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
