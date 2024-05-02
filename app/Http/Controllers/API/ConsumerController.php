<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Consumer;
use App\Models\User;
use Illuminate\Http\Request;

class ConsumerController extends Controller
{
    public function index()
    {
        $per_page = request('per_page', 10);
        $consumers = Consumer::query();

        $user = auth()->user();

        if ($user->role !== 'admin') {
            $consumers->where('user_id', $user->id);
        }

        $consumers = $consumers->with(
            [
                'promoter',
                'competitorBrand',
                'refusedReasons',
                'outlet'
            ]
        )->paginate($per_page);

        return custom_success(200, 'Consumers', $consumers);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'reason_for_refusal_ids' => 'required|array',
                'other_refused_reason' => 'nullable|string',
            ]);

            $user = User::find(auth()->id());
            $outlet_id = $user->attendanceRecords()->latest()->first()->outlet_id;

            $consumer = Consumer::create([
                'user_id' => auth()->id(),
                'outlet_id' => $outlet_id,
                'name' => $request->name,
                'telephone' => $request->telephone,
                'competitor_brand_id' => $request->competitor_brand_id,
                'franchise' => $request->franchise,
                'did_he_switch' => $request->input('did_he_switch', 0),
                'aspen' => $request->aspen,
                'packs' => $request->packs,
                'incentives' => $request->incentives,
                'age' => $request->age,
                'nationality' => $request->nationality,
                'gender' => $request->gender,
            ]);

            foreach ($request->reason_for_refusal_ids as $reasonId) {
                $consumer->refusedReasons()->attach($reasonId, ['other_refused_reason' => $request->input('other_refused_reason')]);
            }

            return custom_success(200, 'Consumer created successfully', $consumer);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
