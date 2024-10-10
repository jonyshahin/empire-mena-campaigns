<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Consumer;
use Illuminate\Http\Request;
use Traversable;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $campaign = Campaign::find($request->campaign_id);
        $campaign_target = $campaign->target;

        $total_contacts = Consumer::where('campaign_id', $request->campaign_id)->get()->count();
        $total_contacts_percentage = $total_contacts / $campaign_target * 100;
        $total_contacts_ratio = $total_contacts / $campaign_target;

        $total_contacts_data = [
            'name' => 'Total Contacts',
            'value' => $total_contacts,
            'percentage' => $total_contacts_percentage,
            'ratio' => $total_contacts_ratio,
        ];

        $effective_contacts = Consumer::where('campaign_id', $request->campaign_id)
            ->where('packs', '>', 0)
            ->get()->count();
        $effective_contacts_percentage = $effective_contacts / $campaign_target * 100;
        $effective_contacts_ratio = $effective_contacts / $campaign_target;

        $effective_contacts_data = [
            'name' => 'Effective Contacts',
            'value' => $effective_contacts,
            'percentage' => $effective_contacts_percentage,
            'ratio' => $effective_contacts_ratio,
        ];

        $trial_rate = [
            'total_contacts_data' => $total_contacts_data,
            'effective_contacts_data' => $effective_contacts_data,
        ];

        $data = [
            'trial_rate' => $trial_rate,
        ];

        return custom_success(200, 'Success', $data);
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
