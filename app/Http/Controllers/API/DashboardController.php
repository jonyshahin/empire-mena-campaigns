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


        $data = [
            'campaign' => $campaign,
            'trial_rate' => $trial_rate,
            'city_performance' => $city_performance,
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
        //////////////////////////////////////////////
    }

    protected function city_performance($campaign)
    {
        $campaign_id = $campaign->id;
        $districts = District::with(['outlets.consumers' => function ($query) use ($campaign_id) {
            $query->where('campaign_id', $campaign_id);
        }])->get();

        $reportData = $districts->map(function ($district) {
            $outlets = $district->outlets;

            return [
                'district' => $district->name,
                'total_consumers_in_district' => $outlets->sum(function ($outlet) {
                    return $outlet->consumers->count();
                }),
                'total_effective_consumers_in_district' => $outlets->sum(function ($outlet) {
                    return $outlet->consumers->where('packs', '>', 0)->count();
                }),
            ];
        });

        $city_performance_data = [
            $reportData,
        ];

        return $city_performance_data;
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
