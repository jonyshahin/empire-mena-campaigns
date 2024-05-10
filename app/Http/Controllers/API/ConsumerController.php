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
                'competitor_brand_id' => $request->input('competitor_brand_id'),
                'other_brand_name' => $request->input('other_brand_name'),
                'franchise' => $request->input('franchise', 0),
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

    public function show(Request $request)
    {
        try {
            $request->validate([
                'consumer_id' => 'required|integer',
            ]);

            $user = auth()->user();

            $consumer = Consumer::query()->where('id', $request->consumer_id);

            if ($user->role !== 'admin') {
                $consumer->where('user_id', $user->id);
            }

            $consumer = $consumer->with(
                [
                    'promoter',
                    'competitorBrand',
                    'refusedReasons',
                    'outlet'
                ]
            )->first();

            return custom_success(200, 'Consumer retrieved Successfuly', $consumer);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'consumer_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'reason_for_refusal_ids' => 'required|array',
                'other_refused_reason' => 'nullable|string',
            ]);
            $consumer = Consumer::find($request->consumer_id);
            if (!$consumer) {
                return custom_error(404, 'Consumer not found');
            }
            $consumer->update([
                'name' => $request->input('name', $consumer->name),
                'telephone' => $request->input('telephone', $consumer->telephone),
                'competitor_brand_id' => $request->input('competitor_brand_id', $consumer->competitor_brand_id),
                'other_brand_name' => $request->input('other_brand_name', $consumer->other_brand_name),
                'franchise' => $request->input('franchise', $consumer->franchise),
                'did_he_switch' => $request->input('did_he_switch', $consumer->did_he_switch),
                'aspen' => $request->input('aspen', $consumer->aspen),
                'packs' => $request->input('packs', $consumer->packs),
                'incentives' => $request->input('incentives', $consumer->incentives),
                'age' => $request->input('age', $consumer->age),
                'nationality' => $request->input('nationality', $consumer->nationality),
                'gender' => $request->input('gender', $consumer->gender),
            ]);

            $consumer->refusedReasons()->detach();

            foreach ($request->reason_for_refusal_ids as $reasonId) {
                $consumer->refusedReasons()->attach($reasonId, ['other_refused_reason' => $request->input('other_refused_reason')]);
            }
            return custom_success(200, 'Consumer updated successfully', $consumer);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'consumer_id' => 'required|integer',
            ]);

            $consumer = Consumer::find($request->consumer_id);

            if (!$consumer) {
                return custom_error(404, 'Consumer not found');
            }

            $consumer->delete();

            return custom_success(200, 'Consumer deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
