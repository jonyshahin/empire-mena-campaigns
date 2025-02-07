<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Consumer;
use App\Models\District;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function generalStatistics($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        $general_statistics = [];

        // Fetch daily logins data
        $dailyLogins = $this->getDailyLogins($campaign, $district_id, $start_date, $end_date);

        // Get visits count using the new function
        $visits = $this->calculateTotalVisits($dailyLogins);

        // Calculate targets
        $campaign_total_target = $this->calculateCampaignTotalTarget($campaign, $visits);
        $campaign_effective_target = $this->calculateCampaignEffectiveTarget($campaign, $visits);

        $total_franchise = $this->totalFranchise($campaign, $district_id, $start_date, $end_date);
        $total_contacts = $this->totalContacts($campaign, $district_id, $start_date, $end_date);
        $effective_contacts = $this->effectiveContacts($campaign, $district_id, $start_date, $end_date);
        $total_refusals = $total_contacts - $effective_contacts;
        $lvl1_incentive_count = $this->lvl1Incentives($campaign, $district_id, $start_date, $end_date);
        $lvl2_incentive_count = $this->lvl2Incentives($campaign, $district_id, $start_date, $end_date);

        $total_switched = $lvl1_incentive_count + $lvl2_incentive_count - $total_franchise;

        $general_statistics['campaign_promoters_count'] = max(array_column($dailyLogins, 'login_count'));
        $general_statistics['visits'] = $visits;
        $general_statistics['total_contacts'] = $total_contacts;
        $general_statistics['effective_contacts'] = $effective_contacts;
        $general_statistics['total_franchise'] = $total_franchise;
        $general_statistics['total_switched'] = $total_switched;
        $general_statistics['total_refusals'] = $total_refusals;
        $general_statistics['lvl1_incentive_count'] = $lvl1_incentive_count;
        $general_statistics['lvl2_incentive_count'] = $lvl2_incentive_count;
        $general_statistics['lvl1_incentive_percentage'] = $effective_contacts > 0 ? ($lvl1_incentive_count / $effective_contacts * 100) : 0;
        $general_statistics['lvl2_incentive_percentage'] = $effective_contacts > 0 ? ($lvl2_incentive_count / $effective_contacts * 100) : 0;
        $general_statistics['campaign_active_days_count'] = count($dailyLogins);
        $general_statistics['campaign_total_target'] = $campaign_total_target;
        $general_statistics['campaign_effective_target'] = $campaign_effective_target;
        $general_statistics['daily_logins'] = $dailyLogins;

        return $general_statistics;
    }

    public function calculateCampaignEffectiveTarget($campaign, $visits)
    {
        return $campaign->effective_contact_target * $visits;
    }

    public function calculateCampaignTotalTarget($campaign, $visits)
    {
        return $campaign->target * $visits;
    }

    public function calculateSalesPerformance($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        // Get all consumers of the campaign
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })->get();

        // Prepare arrays to hold monthly performance data
        $monthlyPerformance = [];
        $effectiveConsumers = [];

        // Loop through consumers and group by month
        foreach ($consumers as $consumer) {
            $createdAt = Carbon::parse($consumer->created_at);
            $monthKey = $createdAt->format('Y-m'); // Group by Year-Month (e.g., "2024-10")

            // Initialize counts for the month if not already set
            if (!isset($monthlyPerformance[$monthKey])) {
                $monthlyPerformance[$monthKey] = 0;
                $effectiveConsumers[$monthKey] = 0;
            }

            // Count total consumers for the month
            $monthlyPerformance[$monthKey]++;


            // Check if the consumer is effective (packs > 0)
            $totalPacks = $consumer->packs;

            // If total packs are greater than 0, count as effective consumer
            if ($totalPacks > 0) {
                $effectiveConsumers[$monthKey]++;
            }
        }

        // Prepare the sales performance response
        $salesPerformance = [];

        foreach ($monthlyPerformance as $month => $totalConsumers) {
            $trial_rate_percentage = ($effectiveConsumers[$month] / $totalConsumers) * 100;
            $salesPerformance[] = [
                'month' => $month,
                'total_consumers' => $totalConsumers,
                'effective_consumers' => $effectiveConsumers[$month],
                'trial_rate_percentage' => $trial_rate_percentage,
            ];
        }

        return $salesPerformance;
    }

    public function totalContacts($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        // Get all consumers of the campaign
        return Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })->count();
    }

    public function effectiveContacts($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        // Get all consumers of the campaign
        return Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })->where('packs', '>', 0)
            ->count();
    }

    public function lvl1Incentives($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        return Consumer::where('campaign_id', $campaign->id)
            ->where('incentives', 'lvl1')
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->count();
    }

    public function totalFranchise($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        return Consumer::where('campaign_id', $campaign->id)
            ->where('franchise', 1)
            ->where('packs', '>', 0)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->count();
    }

    public function lvl2Incentives($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        return Consumer::where('campaign_id', $campaign->id)
            ->where('incentives', 'lvl2')
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->count();
    }

    public function getDailyLogins($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        return Consumer::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as login_count'))
            ->where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()->map(function ($record) {
                return [
                    'date' => $record->date,
                    'login_count' => $record->login_count,
                ];
            })
            ->toArray();
    }

    public function calculateTotalVisits($dailyLogins)
    {
        return array_sum(array_column($dailyLogins, 'login_count'));
    }

    public function trialRate($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        $total_contacts = $this->totalContacts($campaign, $district_id, $start_date, $end_date);
        $visits = $this->calculateTotalVisits($this->getDailyLogins($campaign, $district_id, $start_date, $end_date));
        $campaign_total_target = $this->calculateCampaignTotalTarget($campaign, $visits);
        $campaign_effective_target = $this->calculateCampaignEffectiveTarget($campaign, $visits);
        $total_contacts_ratio = $campaign_total_target > 0 ? ($total_contacts / $campaign_total_target) : 0;
        $total_contacts_percentage = $total_contacts_ratio * 100;


        $total_contacts_data = [
            'name' => 'Total Contacts vs Target',
            'value' => $total_contacts,
            'percentage' => $total_contacts_percentage,
            'ratio' => $total_contacts_ratio,
        ];

        $effective_contacts = $this->effectiveContacts($campaign, $district_id, $start_date, $end_date);
        $effective_contacts_percentage = $effective_contacts / $campaign_effective_target * 100;
        $effective_contacts_ratio = $effective_contacts / $campaign_effective_target;

        $effective_contacts_data = [
            'name' => 'Effective Contacts vs Effective Target',
            'value' => $effective_contacts,
            'percentage' => $effective_contacts_percentage,
            'ratio' => $effective_contacts_ratio,
        ];

        $trial_rate = [
            $total_contacts_data,
            $effective_contacts_data,
        ];

        return $trial_rate;
    }

    public function cityPerformance($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        $campaign_id = $campaign->id;

        $districts = District::with(['outlets.consumers' => function ($query) use ($campaign_id) {
            $query->where('campaign_id', $campaign_id);
        }])->when($district_id, function ($query, $district_id) {
            return $query->where('id', $district_id);
        })->when($start_date, function ($query, $start_date) {
            return $query->whereDate('created_at', '>=', $start_date);
        })->when($end_date, function ($query, $end_date) {
            return $query->whereDate('created_at', '<=', $end_date);
        })->get();

        $total_contacts = $this->totalContacts($campaign, $district_id, $start_date, $end_date);

        $city_performance_data = $districts->map(function ($district) use ($total_contacts) {
            $outlets = $district->outlets;
            $total_contacts_in_district = $outlets->sum(function ($outlet) {
                return $outlet->consumers->count();
            });
            $district_consumers_percentage = $total_contacts > 0 ? ($total_contacts_in_district / $total_contacts * 100) : 0;
            $total_effective_contacts_in_district = $outlets->sum(function ($outlet) {
                return $outlet->consumers->where('packs', '>', 0)->count();
            });

            return [
                'district' => $district->name,
                'total_consumers_in_district' => $total_contacts_in_district,
                'district_consumers_percentage' => $district_consumers_percentage,
            ];
        });

        return $city_performance_data;
    }

    public function genderChart($campaign, $district_id = null, $start_date = null, $end_date = null)
    {
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })
            ->when($start_date, function ($query, $start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })->when($end_date, function ($query, $end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->get();

        $male_consumers = $consumers->where('gender', 'male')->count();
        $female_consumers = $consumers->where('gender', 'female')->count();
        $consumers_count = $consumers->count();
        $male_consumers_percentage = $consumers_count > 0 ? ($male_consumers / $consumers_count * 100) : 0;
        $female_consumers_percentage = $consumers_count > 0 ? ($female_consumers / $consumers_count * 100) : 0;

        $male_data = [
            'name' => 'Male',
            'value' => $male_consumers,
            'percentage' => $male_consumers_percentage,
        ];

        $female_data = [
            'name' => 'Female',
            'value' => $female_consumers,
            'percentage' => $female_consumers_percentage,
        ];

        $gender_chart = [
            $male_data,
            $female_data,
        ];

        return $gender_chart;
    }
}
