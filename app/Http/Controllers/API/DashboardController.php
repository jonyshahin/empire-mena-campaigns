<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Campaign;
use App\Models\Consumer;
use App\Models\District;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Traversable;

class DashboardController extends Controller
{
    protected $total_contacts;
    protected $effective_contacts;
    protected $campaign_active_days_count;
    protected $campaign_promoters_count;
    protected $campaign_total_target;
    protected $campaign_effective_target;
    protected $total_franchise;
    protected $total_switched;
    public function index(Request $request)
    {

        $campaign = Campaign::find($request->campaign_id);
        $district_id = $request->input('district_id');

        $general_statistics = $this->general_statistics($campaign, $district_id);
        $trial_rate = $this->trial_rate($campaign, $district_id);
        $city_performance = $this->city_performance($campaign, $district_id);
        $gender_chart = $this->gender_chart($campaign, $district_id);
        $age_group = $this->age_group($campaign, $district_id);
        $efficiency_rate = $this->efficiency_rate($campaign, $district_id);
        $sales_performance = $this->calculateSalesPerformance($campaign, $district_id);

        $data = [
            'campaign' => $campaign,
            'trial_rate' => $trial_rate,
            'city_performance' => $city_performance,
            'gender_chart' => $gender_chart,
            'age_group' => $age_group['data'],
            'variant_split' => $age_group['variant_split'],
            'packs_sold' => $age_group['packs_sold'],
            'top_competitor_products' => $age_group['top_competitor_products'],
            'efficiency_rate' => $efficiency_rate,
            'sales_performance' => $sales_performance,
            'general_statistics' => $general_statistics,
        ];

        return custom_success(200, 'Success', $data);
    }

    protected function trial_rate($campaign, $district_id = null)
    {
        $total_contacts = $this->total_contacts;
        $campaign_total_target = $this->campaign_total_target;
        $campaign_effective_target = $this->campaign_effective_target;
        $total_contacts_percentage = $total_contacts / $campaign_total_target * 100;
        $total_contacts_ratio = $total_contacts / $campaign_total_target;

        $total_contacts_data = [
            'name' => 'Total Contacts',
            'value' => $total_contacts,
            'percentage' => $total_contacts_percentage,
            'ratio' => $total_contacts_ratio,
        ];

        $effective_contacts = $this->effective_contacts;
        $effective_contacts_percentage = $effective_contacts / $campaign_total_target * 100;
        $effective_contacts_ratio = $effective_contacts / $campaign_total_target;

        $effective_contacts_data = [
            'name' => 'Effective Contacts',
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

    protected function city_performance($campaign, $district_id = null)
    {
        $campaign_id = $campaign->id;

        $districts = District::with(['outlets.consumers' => function ($query) use ($campaign_id) {
            $query->where('campaign_id', $campaign_id);
        }])->when($district_id, function ($query, $district_id) {
            return $query->where('id', $district_id);
        })->get();

        $total_contacts = $this->total_contacts;

        $city_performance_data = $districts->map(function ($district) use ($total_contacts) {
            $outlets = $district->outlets;
            $total_contacts_in_district = $outlets->sum(function ($outlet) {
                return $outlet->consumers->count();
            });
            $district_consumers_percentage = $total_contacts_in_district / $total_contacts * 100;
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

    protected function gender_chart($campaign, $district_id = null)
    {
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->get();
        $male_consumers = $consumers->where('gender', 'male')->count();
        $female_consumers = $consumers->where('gender', 'female')->count();
        $consumers_count = $consumers->count();
        $male_consumers_percentage = $male_consumers / $consumers_count * 100;
        $female_consumers_percentage = $female_consumers / $consumers_count * 100;

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

    protected function age_group($campaign, $district_id = null)
    {
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->get();
        $campaign_products = $campaign->products;
        $ageGroups = ['18-24', '25-34', '35+'];
        $productCounts = [];
        $totalPacksByAgeGroup = [];
        $totalPacksInCampaign = 0; // To count total packs in the campaign
        $competitorProductCounts = []; // To store competitor product counts for each product

        // Initialize counts for each product in each age group
        foreach ($ageGroups as $ageGroup) {
            foreach ($campaign_products as $product) {
                $productCounts[$ageGroup][$product->id] = 0;
                $competitorProductCounts[$product->id] = []; // Initialize competitor product counts
            }
            // Initialize total packs per age group
            $totalPacksByAgeGroup[$ageGroup] = 0;
        }

        // Loop through consumers and count products for each age group
        foreach ($consumers as $consumer) {
            $selectedProducts = $consumer->selected_products;

            // Loop through the selected products for each consumer
            foreach ($selectedProducts as $selectedProduct) {
                $productId = $selectedProduct['id'];
                $packs = (int) $selectedProduct['packs']; // Get the number of packs or quantity
                $competitorProductId = $consumer->competitor_product_id;

                // Add count to the appropriate age group and product
                if (array_key_exists($productId, $productCounts[$consumer->age])) {
                    $productCounts[$consumer->age][$productId] += $packs;
                    $totalPacksByAgeGroup[$consumer->age] += $packs; // Increment the total packs for this age group
                    $totalPacksInCampaign += $packs; // Increment total packs for the campaign
                }

                // Track competitor product counts for the product
                if ($competitorProductId) {
                    if (!isset($competitorProductCounts[$productId][$competitorProductId])) {
                        $competitorProductCounts[$productId][$competitorProductId] = 0;
                    }
                    $competitorProductCounts[$productId][$competitorProductId] += $packs;
                }
            }
        }

        // Prepare the response structure
        $ageGroupData = [];
        $campaignProductPercentage = []; // To hold the percentage of each product in the campaign
        $campaignPacksSold = [];
        $topCompetitorProducts = []; // To store top 3 competitor products for each product

        foreach ($campaign_products as $product) {
            $productData = [
                'product' => $product,  // Assuming $product contains id, name, image, etc.
            ];

            $variantSplit = [
                'product_name' => $product->name,
            ];

            $packsSold = [
                'product' => $product,
            ];

            // Initialize total product count in the campaign
            $totalProductCountInCampaign = 0;

            foreach ($ageGroups as $ageGroup) {
                $totalPacks = $totalPacksByAgeGroup[$ageGroup];
                $productCount = $productCounts[$ageGroup][$product->id];

                // Avoid division by zero
                if ($totalPacks > 0) {
                    $percentage = ($productCount / $totalPacks) * 100;
                } else {
                    $percentage = 0;
                }

                // Add the value and percentage for this age group
                $productData[$ageGroup] = [
                    'value' => $productCount,
                    'percentage' => round($percentage, 2), // Rounded to 2 decimal places
                ];

                // Sum up the product count across all age groups for the campaign-level calculation
                $totalProductCountInCampaign += $productCount;
            }

            // Calculate percentage of the product in the whole campaign
            if ($totalPacksInCampaign > 0) {
                $campaignPercentage = ($totalProductCountInCampaign / $totalPacksInCampaign) * 100;
            } else {
                $campaignPercentage = 0;
            }

            // Add the product's campaign percentage
            $variantSplit['campaign_percentage'] = round($campaignPercentage, 2);
            $packsSold['packs_sold'] = $totalProductCountInCampaign;

            // Get top 3 competitor products for this product
            if (isset($competitorProductCounts[$product->id])) {
                $competitors = $competitorProductCounts[$product->id];
                arsort($competitors); // Sort by count descending
                $top3Competitors = array_slice($competitors, 0, 3, true); // Get top 3 competitors

                // Calculate percentage for each competitor product
                $totalCompetitorPacks = array_sum($competitors);
                $topCompetitorsData = [];

                foreach ($top3Competitors as $competitorId => $competitorCount) {
                    $competitorPercentage = ($competitorCount / $totalCompetitorPacks) * 100;
                    $topCompetitorsData[] = [
                        'competitor_product' => Product::find($competitorId),
                        'value' => $competitorCount,
                        'percentage' => round($competitorPercentage, 2),
                    ];
                }

                // Store top competitors for the product
                $topCompetitorProducts[] = [
                    'product' => $product,
                    'campaign_percentage' => round($campaignPercentage, 2),
                    'top_competitors' => $topCompetitorsData
                ];
            }


            // Add the product data to the response
            $ageGroupData[] = $productData;
            $campaignProductPercentage[] = $variantSplit;
            $campaignPacksSold[] = $packsSold;
        }

        // Prepare final response
        return [
            'data' => $ageGroupData,
            'variant_split' => $campaignProductPercentage,
            'packs_sold' => $campaignPacksSold,
            'top_competitor_products' => $topCompetitorProducts,
        ];
    }

    protected function efficiency_rate($campaign, $district_id = null)
    {
        // Get all consumers of the campaign
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->get();

        $competitorProductCounts = []; // To store competitor product counts
        $competitorSwitchCounts = []; // To store switch counts for each competitor product

        // Loop through consumers and count competitor products and switches
        foreach ($consumers as $consumer) {
            $competitorProductId = $consumer->competitor_product_id;
            $didHeSwitch = $consumer->did_he_switch; // Check if the consumer switched

            if ($competitorProductId) {
                // Track competitor product counts
                if (!isset($competitorProductCounts[$competitorProductId])) {
                    $competitorProductCounts[$competitorProductId] = 0;
                }
                $competitorProductCounts[$competitorProductId]++;

                // Track switch count for this competitor product
                if (!isset($competitorSwitchCounts[$competitorProductId])) {
                    $competitorSwitchCounts[$competitorProductId] = 0;
                }
                if ($didHeSwitch) {
                    $competitorSwitchCounts[$competitorProductId]++;
                }
            }
        }

        // Get the top 5 competitor products across the campaign
        arsort($competitorProductCounts); // Sort by count descending
        $top5Competitors = array_slice($competitorProductCounts, 0, 5, true); // Get top 5 competitors

        // Calculate the percentage of switches for the top 5 competitor products
        $topCompetitorSwitches = [];

        foreach ($top5Competitors as $competitorId => $competitorCount) {
            $switchCount = $competitorSwitchCounts[$competitorId] ?? 0; // Get switch count for this competitor
            $switchPercentage = ($switchCount / $competitorCount) * 100; // Calculate percentage of switches

            $topCompetitorSwitches[] = [
                'competitor_product' => Product::find($competitorId),
                'value' => $competitorCount,
                'switch_count' => $switchCount,
                'switch_percentage' => round($switchPercentage, 2), // Rounded to 2 decimal places
            ];
        }

        // Return the top 5 competitor products with their switch rates
        return $topCompetitorSwitches;
    }

    public function update_consumer_packs(Request $request)
    {
        $consumers = Consumer::where('campaign_id', $request->campaign_id)->get();

        foreach ($consumers as $consumer) {
            $selected_products = $consumer->selected_products;
            $packs = 0;
            if (is_array($selected_products) || $selected_products instanceof Traversable) {
                foreach ($selected_products as $product) {
                    if (is_array($product) && array_key_exists('packs', $product)) {
                        $packs += intval($product['packs']);
                    }
                }
            }
            $consumer->packs = $packs;
            $consumer->save();
        }

        return custom_success(200, 'Packs updated successfully', []);
    }

    protected function calculateSalesPerformance($campaign, $district_id = null)
    {
        // Get all consumers of the campaign
        $consumers = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
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

    protected function general_statistics($campaign, $district_id = null)
    {
        $general_statistics = [];

        $this->campaign_promoters_count = AttendanceRecord::where('campaign_id', $campaign->id)
            ->whereNotNull('check_in_time') // Ensure only records with check-ins are counted
            ->distinct('user_id')
            ->count('user_id');

        $dailyLogins = AttendanceRecord::select(DB::raw('DATE(check_in_time) as date'), DB::raw('COUNT(DISTINCT user_id) as login_count'))
            ->where('campaign_id', $campaign->id)
            ->whereNotNull('check_in_time') // Ensure only records with check-ins are counted
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()->map(function ($record) {
                return [
                    'date' => $record->date,
                    'login_count' => $record->login_count,
                ];
            })
            ->toArray();

        // Count the number of elements in the $dailyLogins array
        $this->campaign_active_days_count = count($dailyLogins);
        $visits = 0;
        foreach ($dailyLogins as $login) {
            $visits += $login['login_count'];
        }

        $this->total_contacts = Consumer::where('campaign_id', $campaign->id)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })->get()
            ->count();

        $this->effective_contacts = Consumer::where('campaign_id', $campaign->id)
            ->where('packs', '>', 0)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })
            ->get()->count();

        $this->total_franchise = Consumer::where('campaign_id', $campaign->id)
            ->where('franchise', 1)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })
            ->get()->count();

        $this->total_switched = Consumer::where('campaign_id', $campaign->id)
            ->where('did_he_switch', '<>', 0)
            ->when($district_id, function ($query, $district_id) {
                return $query->whereHas('outlet', function ($query) use ($district_id) {
                    $query->where('district_id', $district_id);
                });
            })
            ->get()->count();

        $this->campaign_total_target = $campaign->target * $this->campaign_active_days_count * $this->campaign_promoters_count;
        $this->campaign_effective_target = $campaign->effective_contact_target * $this->campaign_active_days_count * $this->campaign_promoters_count;

        $general_statistics['campaign_promoters_count'] = $this->campaign_promoters_count;
        $general_statistics['visits'] = $visits;
        $general_statistics['total_contacts'] = $this->total_contacts;
        $general_statistics['effective_contacts'] = $this->effective_contacts;
        $general_statistics['total_franchise'] = $this->total_franchise;
        $general_statistics['total_switched'] = $this->total_switched;
        $general_statistics['campaign_active_days_count'] = $this->campaign_active_days_count;
        $general_statistics['campaign_total_target'] = $this->campaign_total_target;
        $general_statistics['campaign_effective_target'] = $this->campaign_effective_target;

        return $general_statistics;
    }
}
