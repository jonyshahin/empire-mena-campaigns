<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Consumer;
use App\Models\District;
use Illuminate\Http\Request;
use Traversable;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $campaign = Campaign::find($request->campaign_id);

        $trial_rate = $this->trial_rate($campaign);
        $city_performance = $this->city_performance($campaign);
        $gender_chart = $this->gender_chart($campaign);
        $age_group = $this->age_group($campaign);

        $data = [
            'campaign' => $campaign,
            'trial_rate' => $trial_rate,
            'city_performance' => $city_performance,
            'gender_chart' => $gender_chart,
            'age_group' => $age_group,
        ];

        return custom_success(200, 'Success', $data);
    }

    protected function trial_rate($campaign)
    {
        // Calculate trial rate data
        $total_contacts = Consumer::where('campaign_id', $campaign->id)->get()->count();
        $total_contacts_percentage = $total_contacts / $campaign->target * 100;
        $total_contacts_ratio = $total_contacts / $campaign->target;

        $total_contacts_data = [
            'name' => 'Total Contacts',
            'value' => $total_contacts,
            'percentage' => $total_contacts_percentage,
            'ratio' => $total_contacts_ratio,
        ];

        $effective_contacts = Consumer::where('campaign_id', $campaign->id)
            ->where('packs', '>', 0)
            ->get()->count();
        $effective_contacts_percentage = $effective_contacts / $campaign->target * 100;
        $effective_contacts_ratio = $effective_contacts / $campaign->target;

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

    protected function city_performance($campaign)
    {
        $campaign_id = $campaign->id;
        $districts = District::with(['outlets.consumers' => function ($query) use ($campaign_id) {
            $query->where('campaign_id', $campaign_id);
        }])->get();
        $total_contacts = Consumer::where('campaign_id', $campaign->id)->get()->count();

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

    protected function gender_chart($campaign)
    {
        $consumers = Consumer::where('campaign_id', $campaign->id)->get();
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

    protected function age_group($campaign)
    {
        $consumers = Consumer::where('campaign_id', $campaign->id)->get();
        $campaign_products = $campaign->products;
        $ageGroups = ['18-24', '25-34', '35+'];
        $productCounts = [];

        // Initialize counts for each product in each age group
        foreach ($ageGroups as $ageGroup) {
            foreach ($campaign_products as $product) {
                $productCounts[$ageGroup][$product->id] = 0;
            }
        }

        // Loop through consumers and count products for each age group
        foreach ($consumers as $consumer) {
            $selectedProducts = $consumer->selected_products;

            // Loop through the selected products for each consumer
            foreach ($selectedProducts as $selectedProduct) {
                $productId = $selectedProduct['id'];
                $packs = (int) $selectedProduct['packs']; // Get the number of packs or quantity

                // Add count to the appropriate age group and product
                if (array_key_exists($productId, $productCounts[$consumer->age])) {
                    $productCounts[$consumer->age][$productId] += $packs;
                }
            }
        }

        $age_group_data = $productCounts;

        return $age_group_data;
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
}
