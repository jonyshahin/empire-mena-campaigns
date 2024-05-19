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

    public function __construct($date = null, $district_id = null)
    {
        $this->date = $date;
        $this->district_id = $district_id;
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
            $date = $this->date;
            $districtId = $this->district_id;

            // $timezone = $user ? $user->timezone : 'UTC';
            $timezone = 'Asia/Baghdad';


            // Retrieve districts based on the presence of district_id
            $districtsQuery = District::with(['outlets.consumers' => function ($query) use ($date) {
                if ($date) {
                    $query->whereDate('created_at', $date);
                }
                $query->orderBy('created_at', 'desc');
            }]);

            if ($districtId) {
                $districtsQuery->where('id', $districtId);
            }

            $districts = $districtsQuery->get();

            // Transform data to include time zone conversion
            $reportData = $districts->map(function ($district) use ($timezone) {
                return [
                    'district' => $district->name,
                    'outlet_count' => $district->outlets->count(),
                    'total_consumers_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->count();
                    }),
                    'total_effective_consumers_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('packs', '>', 0)->count();
                    }),
                    'total_packs_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->sum('packs');
                    }),
                    'total_incentive_lvl1_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl1')->count();
                    }),
                    'total_incentive_lvl2_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl2')->count();
                    }),
                    'total_franchise_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('franchise', true)->count();
                    }),
                    'total_did_he_switch_in_district' => $district->outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('did_he_switch', true)->count();
                    }),
                    'outlets' => $district->outlets->map(function ($outlet) use ($timezone) {
                        return [
                            'outlet' => $outlet->name,
                            'consumer_count' => $outlet->consumers->count(),
                            'effective_consumer_count' => $outlet->consumers->where('packs', '>', 0)->count(),
                            'total_packs_in_outlet' => $outlet->consumers->sum('packs'),
                            'total_incentive_lvl1_in_outlet' => $outlet->consumers->where('incentives', 'lvl1')->count(),
                            'total_incentive_lvl2_in_outlet' => $outlet->consumers->where('incentives', 'lvl2')->count(),
                            'total_franchise_in_outlet' => $outlet->consumers->where('franchise', true)->count(),
                            'total_did_he_switch_in_outlet' => $outlet->consumers->where('did_he_switch', true)->count(),
                            // 'consumers' => $outlet->consumers->map(function ($consumer) use ($timezone) {
                            //     return [
                            //         'id' => $consumer->id,
                            //         'name' => $consumer->name,
                            //         'packs' => $consumer->packs,
                            //         'incentives' => $consumer->incentives,
                            //         'franchise' => $consumer->franchise,
                            //         'did_he_switch' => $consumer->did_he_switch,
                            //         'created_at' => Carbon::parse($consumer->created_at)->timezone($timezone)->toDateTimeString(),
                            //         'updated_at' => Carbon::parse($consumer->updated_at)->timezone($timezone)->toDateTimeString(),
                            //     ];
                            // }),
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
