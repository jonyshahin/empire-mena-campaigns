<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PromoterTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class PromoterTrackingController extends Controller
{
    public function index()
    {
        $models = PromoterTracking::get();
        return custom_success(200, 'Promoter Trackings', $models);
    }

    public function campaign_promoter_tracking()
    {
        $models = QueryBuilder::for(PromoterTracking::class);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->merge(['user_id' => $user->id]);
        $promoterTracking = PromoterTracking::updateOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'name' => $request->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ]
        );
        return custom_success(200, 'Promoter Tracking', $promoterTracking);
    }
}
