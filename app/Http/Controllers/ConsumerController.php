<?php

namespace App\Http\Controllers;

use App\Models\CompetitorBrand;
use App\Models\Consumer;
use App\Models\RefusedReason;
use Illuminate\Http\Request;

class ConsumerController extends Controller
{
    public function index()
    {
        $consumers = Consumer::query()->with(['promoter', 'competitorBrand', 'refusedReasons'])->get();
        // dd($consumers);
        return view('consumer.index', compact('consumers'));
    }

    public function create()
    {
        $competitorBrands = CompetitorBrand::query()->get();
        $refusedReasons = RefusedReason::query()->get();
        return view('consumer.create', compact('competitorBrands', 'refusedReasons'));
    }

    public function store(Request $request)
    {
        try {
            // $request->validate([
            //     'name' => 'required|string|max:255',
            //     'reason_for_refusal_ids' => 'nullable|array',
            //     'other_refused_reason' => 'nullable|string',
            // ]);
            $request->merge(['reason_for_refusal_ids' => $request->reason_for_refusal_ids ?? []]);
            // dd($request->all());

            $consumer = Consumer::create([
                'user_id' => auth()->id(),
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

            return redirect()->route('consumer')->with('success', 'Consumer created successfully!');
        } catch (\Throwable $th) {
            return redirect()->route('consumer')->with('error', 'Something went wrong!');
        }
    }
}
