<?php

namespace App\Http\Controllers\API;

use App\Exports\ConsumersReportExport;
use App\Http\Controllers\Controller;
use App\Models\Consumer;
use App\Models\District;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ConsumerController extends Controller
{
    public function index()
    {
        $per_page = request('per_page', 10);
        $consumers = Consumer::query();

        $user = Auth::user();
        $user = User::find($user->id);

        if (!$user->hasRole('admin')) {
            $consumers->where('user_id', $user->id);
        }

        if ($user->hasRole('promoter')) {
            $consumers->whereDate('created_at', now()->toDateString());
        }

        if ($search = request('search')) {
            $consumers->search($search);
        }

        $consumers = $consumers->orderBy('created_at', 'desc')->paginate($per_page);

        // Convert created_at and updated_at to the user's timezone
        $consumers->getCollection()->transform(function ($consumer) use ($user) {
            $consumer->created_at = Carbon::parse($consumer->created_at)->timezone('Asia/Baghdad');
            $consumer->updated_at = Carbon::parse($consumer->updated_at)->timezone('Asia/Baghdad');
            return $consumer;
        });

        return custom_success(200, 'Consumers', $consumers);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'reason_for_refusal_ids' => 'nullable|array',
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
                'aspen' => implode(',', $request->aspen),
                'packs' => $request->packs,
                'incentives' => $request->incentives,
                'age' => $request->age,
                'nationality_id' => $request->nationality_id,
                'gender' => $request->gender,
            ]);


            if ($request->filled('reason_for_refusal_ids') && !empty($request->reason_for_refusal_ids)) {
                foreach ($request->reason_for_refusal_ids as $reasonId) {
                    $consumer->refusedReasons()->attach($reasonId, ['other_refused_reason' => $request->input('other_refused_reason')]);
                }
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

            $user = Auth::user();
            $user = User::find($user->id);

            $consumer = Consumer::query()->where('id', $request->consumer_id);

            if (!$user->hasRole('admin')) {
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
                'reason_for_refusal_ids' => 'nullable|array',
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

            $consumer->refusedReasons()->detach();

            $consumer->delete();

            return custom_success(200, 'Consumer deleted successfully', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function report(Request $request)
    {
        try {
            // Validate the date parameter
            $request->validate([
                'date' => 'nullable|date_format:Y-m-d',
                'district_id' => 'nullable|integer|exists:districts,id',
            ]);

            // Get the date from the request
            $date = $request->input('date');
            $districtId = $request->input('district_id');

            // Get the current user's timezone
            $user = Auth::user();

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

            return custom_success(200, 'Report generated successfully', $reportData['outlets']);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Validate the date parameter
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'district_id' => 'nullable|integer|exists:districts,id',
        ]);

        $date = $request->input('date');
        $district_id = $request->input('district_id');

        return Excel::download(new ConsumersReportExport($date, $district_id), 'consumers_report.xlsx');
    }
}
