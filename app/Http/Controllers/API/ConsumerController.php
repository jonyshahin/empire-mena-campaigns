<?php

namespace App\Http\Controllers\API;

use App\Exports\ConsumersByPromoterExport;
use App\Exports\ConsumersReportExport;
use App\Exports\PromoterDailyFeedback;
use App\Exports\PromotersCountByDayExport;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportEmail;
use App\Models\AttendanceRecord;
use App\Models\Consumer;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Traversable;

class ConsumerController extends Controller
{
    public function index(Request $request)
    {
        $per_page = request('per_page', 10);
        $consumers = Consumer::query();

        $user = Auth::user();
        $user = User::find($user->id);

        if ($user->hasRole('promoter')) {
            $campaign_id = $user->attendanceRecords()->latest()->first()->campaign_id;
            $consumers->where('user_id', $user->id)->where('campaign_id', $campaign_id);
            $consumers->whereDate('created_at', now()->toDateString());
        }

        if ($user->hasRole('client')) {
            $company = $user->company;
            $campaign_ids = $company->campaigns->pluck('id');
            $consumers->whereIn('campaign_id', $campaign_ids);
        }

        if ($search = request('search')) {
            $consumers->search($search);
        }

        if ($user->hasRole(['super-admin', 'admin']) && $request->filled('campaign_id')) {
            Log::info($request->campaign_id);
            $consumers->where('campaign_id', $request->campaign_id);
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
            $user = User::find(auth()->id());

            if (!$user->hasRole('promoter')) {
                return custom_error('422', 'Not Authorized to create consumer form');
            }

            $outlet_id = $user->attendanceRecords()->latest()->first()->outlet_id;
            $campaign_id = $user->attendanceRecords()->latest()->first()->campaign_id;

            $request->validate([
                'name' => 'required|string|max:255',
                // 'telephone' => 'nullable|string|max:255',
                'telephone' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('consumers')->where(function ($query) use ($campaign_id) {
                        return $query->where('campaign_id', $campaign_id);
                    }),
                ],
                'reason_for_refusal_ids' => 'nullable|array',
                'other_refused_reason' => 'nullable|string',
                'selected_products' => 'nullable|array',
                'competitor_product_id' => 'nullable|integer',
                'dynamic_incentives' => 'nullable|array',
                'dynamic_incentives.*.id' => 'required|integer|exists:incentives,id',
            ]);

            // $otp_code = rand(100000, 999999);
            // $otp_expired_at = Carbon::now()->addMinutes(15);

            // /*************** Send WhatsApp code********************/
            // if ($request->filled('telephone')) {
            //     $phone = $request->telephone;
            //     $whatsapp_data = whatsapp_message($phone, $otp_code);

            //     if ($whatsapp_data['code'] == 0) {
            //         return custom_error(400, $whatsapp_data['data']);
            //     }
            // }
            /******************************************************/

            $packs = 0;
            $selected_products = $request->input('selected_products');

            if (is_array($selected_products) || $selected_products instanceof Traversable) {
                foreach ($selected_products as $product) {
                    if (is_array($product) && array_key_exists('packs', $product)) {
                        $packs += intval($product['packs']);
                    }
                }
            }

            $consumer = Consumer::create([
                'user_id' => auth()->id(),
                'outlet_id' => $outlet_id,
                'name' => $request->name,
                'telephone' => $request->input('telephone'),
                'competitor_brand_id' => $request->input('competitor_brand_id'),
                'other_brand_name' => $request->input('other_brand_name'),
                'franchise' => $request->input('franchise', 0),
                'did_he_switch' => isset($request->did_he_switch) ? $request->did_he_switch : 0,
                'aspen' => $request->has('aspen') ? implode(',', $request->aspen) : null,
                'packs' => $packs,
                'incentives' => $request->incentives,
                'age' => $request->age,
                'nationality_id' => $request->nationality_id,
                'gender' => $request->gender,
                'campaign_id' => $campaign_id,
                'selected_products' => $request->input('selected_products'),
                'competitor_product_id' => $request->input('competitor_product_id'),
                'dynamic_incentives' => $request->input('dynamic_incentives'),
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

    public function send_otp(Request $request)
    {
        try {
            $otp_code = random_int(100000, 999999);
            $otp_expired_at = Carbon::now()->addMinutes(15);

            /*************** Send WhatsApp code********************/
            if ($request->filled('telephone')) {
                $phone = $request->telephone;
                $whatsapp_data = whatsapp_message($phone, $otp_code);

                if ($whatsapp_data['code'] == 0) {
                    return custom_error(400, $whatsapp_data['data']);
                }
            }
            /******************************************************/

            return custom_success(200, 'OTP sent successfully', $otp_code);
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

            if ($user->hasRole('promoter')) {
                $consumer->where('user_id', $user->id);
            }

            if ($user->hasRole('client')) {
                $company = $user->company;
                $campaign_ids = $company->campaigns->pluck('id');
                $consumer->whereIn('campaign_id', $campaign_ids);
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
                'consumer_id' => 'required|integer|exists:consumers,id',
                'name' => 'required|string|max:255',
                'reason_for_refusal_ids' => 'nullable|array',
                'other_refused_reason' => 'nullable|string',
                'campaign_id' => 'required|integer|exists:campaigns,id',
                'selected_products' => 'nullable|array',
                'competitor_product_id' => 'nullable|integer',
                // 'telephone' => 'nullable|string|max:255',
                'telephone' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('consumers')->where(function ($query) use ($request) {
                        return $query->where('campaign_id', $request->campaign_id);
                    })->ignore($request->consumer_id),
                ],
            ]);

            $consumer = Consumer::find($request->consumer_id);
            if (!$consumer) {
                return custom_error(404, 'Consumer not found');
            }
            // $otp_code = rand(100000, 999999);
            // $otp_expired_at = Carbon::now()->addMinutes(15);
            // /*************** Send WhatsApp code********************/
            // if ($request->filled('telephone')) {
            //     $phone = $request->telephone;
            //     $whatsapp_data = whatsapp_message($phone, $otp_code);

            //     if ($whatsapp_data['code'] == 0) {
            //         return custom_error(400, $whatsapp_data['data']);
            //     }
            // }
            /******************************************************/

            $packs = 0;
            $selected_products = $request->input('selected_products');

            if (is_array($selected_products) || $selected_products instanceof Traversable) {
                foreach ($selected_products as $product) {
                    if (is_array($product) && array_key_exists('packs', $product)) {
                        $packs += intval($product['packs']);
                    }
                }
            }

            $consumer->update([
                'name' => $request->input('name', $consumer->name),
                'telephone' => $request->input('telephone', $consumer->telephone),
                'competitor_brand_id' => $request->input('competitor_brand_id', $consumer->competitor_brand_id),
                'other_brand_name' => $request->input('other_brand_name', $consumer->other_brand_name),
                'franchise' => $request->input('franchise', $consumer->franchise),
                'did_he_switch' => isset($request->did_he_switch) ? $request->did_he_switch : 0,
                'aspen' => $request->input('aspen', $consumer->aspen),
                'packs' => $packs,
                'incentives' => $request->input('incentives', $consumer->incentives),
                'age' => $request->input('age', $consumer->age),
                'nationality_id' => $request->input('nationality_id', $consumer->nationality_id),
                'gender' => $request->input('gender', $consumer->gender),
                'campaign_id' => $request->input('campaign_id', $consumer->campaign_id),
                'selected_products' => $request->input('selected_products', $consumer->selected_products),
                'competitor_product_id' => $request->input('competitor_product_id', $consumer->competitor_product_id),
            ]);

            if ($request->filled('reason_for_refusal_ids') && !empty($request->reason_for_refusal_ids)) {
                $consumer->refusedReasons()->detach();

                foreach ($request->reason_for_refusal_ids as $reasonId) {
                    $consumer->refusedReasons()->attach($reasonId, ['other_refused_reason' => $request->input('other_refused_reason')]);
                }
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
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_id' => 'nullable|integer|exists:districts,id',
                'outlet_id' => 'nullable|integer|exists:outlets,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            // Get the date from the request
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $districtId = $request->input('district_id');
            $outletId = $request->input('outlet_id');
            $campaign_id = $request->input('campaign_id');

            // Get the current user's timezone
            $user = User::find(Auth::user()->id);

            // $timezone = $user ? $user->timezone : 'UTC';
            $timezone = 'Asia/Baghdad';


            // Retrieve districts based on the presence of district_id
            $districtsQuery = District::with(['outlets.consumers' => function ($query) use ($start_date, $end_date, $campaign_id) {
                if ($start_date) {
                    $query->whereDate('created_at', '>=', $start_date);
                }
                if ($end_date) {
                    $query->whereDate('created_at', '<=', $end_date);
                }
                if (isset($campaign_id)) {
                    $query->where('campaign_id', $campaign_id);
                }
                $query->orderBy('created_at', 'desc');
            }]);

            if ($districtId) {
                $districtsQuery->where('id', $districtId);
            }

            if ($outletId) {
                $districtsQuery->whereHas('outlets', function ($query) use ($outletId) {
                    $query->where('id', $outletId);
                });
            }

            $districts = $districtsQuery->get();

            // Transform data to include time zone conversion
            $reportData = $districts->map(function ($district) use ($outletId, $timezone) {
                $outlets = $district->outlets;

                // If outlet_id is provided, filter the outlets collection to include only the specific outlet
                if ($outletId) {
                    $outlets = $outlets->filter(function ($outlet) use ($outletId) {
                        return $outlet->id == $outletId;
                    });
                }

                return [
                    'district' => $district->name,
                    'outlet_count' => $outlets->count(),
                    'total_consumers_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->count();
                    }),
                    'total_effective_consumers_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('packs', '>', 0)->count();
                    }),
                    'total_packs_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->sum('packs');
                    }),
                    'total_incentive_lvl1_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl1')->count();
                    }),
                    'total_incentive_lvl2_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('incentives', 'lvl2')->count();
                    }),
                    'total_franchise_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('franchise', true)->count();
                    }),
                    'total_did_he_switch_in_district' => $outlets->sum(function ($outlet) {
                        return $outlet->consumers->where('did_he_switch', true)->count();
                    }),
                    'outlets' => $outlets->map(function ($outlet) use ($timezone) {
                        return [
                            'outlet' => $outlet->name,
                            'consumer_count' => $outlet->consumers->count(),
                            'effective_consumer_count' => $outlet->consumers->where('packs', '>', 0)->count(),
                            'total_packs_in_outlet' => $outlet->consumers->sum('packs'),
                            'total_incentive_lvl1_in_outlet' => $outlet->consumers->where('incentives', 'lvl1')->count(),
                            'total_incentive_lvl2_in_outlet' => $outlet->consumers->where('incentives', 'lvl2')->count(),
                            'total_franchise_in_outlet' => $outlet->consumers->where('franchise', true)->count(),
                            'total_did_he_switch_in_outlet' => $outlet->consumers->where('did_he_switch', true)->count(),
                        ];
                    }),
                ];
            });

            return custom_success(200, 'Report generated successfully', $reportData);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Validate the date parameter
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'district_id' => 'nullable|integer|exists:districts,id',
            'outlet_id' => 'nullable|integer|exists:outlets,id',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
        ]);

        // Get the date from the request
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $districtId = $request->input('district_id');
        $outletId = $request->input('outlet_id');
        $campaign_id = $request->input('campaign_id');

        return Excel::download(new ConsumersReportExport($start_date, $end_date, $districtId, $outletId, $campaign_id), 'consumers_report.xlsx');
    }

    public function consumersByPromoter(Request $request)
    {
        try {
            // Validate the optional date and district_id parameters
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_ids' => 'nullable|array',
                'district_ids.*' => 'integer|exists:districts,id',
                'competitor_brand_id' => 'nullable|integer|exists:competitor_brands,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
                'competitor_product_ids' => 'nullable|array',
                'competitor_product_ids.*' => 'integer|exists:products,id',
                'promoter_id' => 'nullable|integer|exists:users,id',
            ]);

            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $districtIds = $request->input('district_ids');
            $competitorBrandId = $request->input('competitor_brand_id');
            $promoterId = $request->input('promoter_id');
            $user = User::find(Auth::user()->id);
            $campaign_id = $request->input('campaign_id');
            $competitor_product_ids = [];

            if ($competitorBrandId) {
                $competitor_product_ids = Product::where('brand_id', $competitorBrandId)->pluck('id');
            }

            if ($request->filled('competitor_product_ids')) {
                $competitor_product_ids = $request->input('competitor_product_ids');
            }


            if ($user->hasRole('team_leader')) {
                $campaign_id = $user->attendanceRecords()->latest()->first()->campaign_id;
            }

            $timezone = 'Asia/Baghdad';

            // Retrieve consumers grouped by promoter
            $consumersQuery = Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
                ->when($campaign_id, function ($query, $campaign_id) {
                    return $query->where('campaign_id', $campaign_id);
                })
                ->when($competitor_product_ids, function ($query, $competitor_product_ids) {
                    return $query->whereIn('competitor_product_id', $competitor_product_ids);
                })
                ->when($start_date, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', $date);
                })
                ->when($end_date, function ($query, $date) {
                    return $query->whereDate('created_at', '<=', $date);
                })
                ->when($districtIds, function ($query, $districtIds) {
                    return $query->whereHas('outlet', function ($query) use ($districtIds) {
                        $query->whereIn('district_id', $districtIds);
                    });
                })
                ->when($promoterId, function ($query, $promoterId) {
                    return $query->where('user_id', $promoterId);
                })
                ->get()
                ->groupBy('promoter.name');

            $reportData = $consumersQuery->map(function ($consumers, $promoterName) use ($timezone) {
                return [
                    'promoter' => $promoterName,
                    'consumers' => $consumers->map(function ($consumer) use ($timezone) {
                        return [
                            'outlet' => $consumer->outlet->name,
                            'district' => $consumer->outlet->district->name,
                            'name' => $consumer->name,
                            'packs' => $consumer->packs,
                            'incentives' => $consumer->incentives,
                            'franchise' => $consumer->franchise ? 'Yes' : 'No',
                            'did_he_switch' => $consumer->did_he_switch ? 'Yes' : 'No',
                            'competitor_brand' => optional($consumer->competitorBrand)->name,
                            'other_brand_name' => $consumer->other_brand_name == null ? '' : $consumer->other_brand_name,
                            'aspen' => $consumer->aspen,
                            'refusal_reasons' => $consumer->refusedReasons->map(function ($reason) {
                                return [
                                    'reason' => $reason->name,
                                    'other_reason' => $reason->pivot->other_refused_reason,
                                ];
                            }),
                            'created_at' => Carbon::parse($consumer->created_at)->timezone($timezone)->toDateTimeString(),
                            'updated_at' => Carbon::parse($consumer->updated_at)->timezone($timezone)->toDateTimeString(),
                        ];
                    }),
                ];
            });

            return custom_success(200, 'Report generated successfully', $reportData);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function exportConsumersByPromoter(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'district_ids' => 'nullable|array',
            'district_ids.*' => 'integer|exists:districts,id',
            'competitor_brand_id' => 'nullable|integer|exists:competitor_brands,id',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'competitor_product_ids' => 'nullable|array',
            'competitor_product_ids.*' => 'integer|exists:products,id',
            'promoter_id' => 'nullable|integer|exists:users,id',
        ]);

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $districtIds = $request->input('district_ids');
        $competitorBrandId = $request->input('competitor_brand_id');
        $promoterId = $request->input('promoter_id');
        $campaign_id = $request->input('campaign_id');
        $competitor_product_ids = [];

        if ($competitorBrandId) {
            $competitor_product_ids = Product::where('brand_id', $competitorBrandId)->pluck('id');
        }

        if ($request->filled('competitor_product_ids')) {
            $competitor_product_ids = $request->input('competitor_product_ids');
        }

        $totalCount = Consumer::with('promoter', 'outlet.district', 'competitorBrand', 'refusedReasons')
            ->when($campaign_id, function ($query, $campaign_id) {
                return $query->where('campaign_id', $campaign_id);
            })
            ->when($competitor_product_ids, function ($query, $competitor_product_ids) {
                return $query->whereIn('competitor_product_id', $competitor_product_ids);
            })
            ->when($start_date, function ($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($end_date, function ($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->when($districtIds, function ($query, $districtIds) {
                return $query->whereHas('outlet', function ($query) use ($districtIds) {
                    $query->whereIn('district_id', $districtIds);
                });
            })
            ->when($promoterId, function ($query, $promoterId) {
                return $query->where('user_id', $promoterId);
            })
            ->count();

        $fileName = 'consumers_' . now()->timestamp . '.xlsx';
        $filePath = '/home/empiremena/public_html/api-campaign.empire-mena.com/storage/app/' . $fileName;

        Excel::queue(new ConsumersByPromoterExport(
            $start_date,
            $end_date,
            $districtIds,
            $promoterId,
            $campaign_id,
            $competitor_product_ids,
            $totalCount
        ), $fileName)->chain([
            new SendExportEmail('jony.shahin@gmail.com', $filePath),
            new SendExportEmail('h.saad@empire-mena.com', $filePath),
        ]);

        return custom_success(200, 'Export started. You will receive an email once it is ready.', []);

        // return Excel::download(new ConsumersByPromoterExport($start_date, $end_date, $districtIds, $promoterId, $campaign_id, $competitor_product_ids), 'consumers_by_promoter_report.xlsx');
    }

    public function promotersCountByDay(Request $request)
    {
        try {
            // Validate the optional period parameters
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                // 'period' => 'nullable|string|in:week,month,last_week,last_month',
                'district_id' => 'nullable|integer|exists:districts,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            // $period = $request->input('period');
            $districtId = $request->input('district_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $campaign_id = $request->input('campaign_id');

            // Determine the start and end dates based on the period
            // switch ($period) {
            //     case 'week':
            //         $startDate = Carbon::now()->startOfWeek();
            //         $endDate = Carbon::now()->endOfWeek();
            //         break;
            //     case 'month':
            //         $startDate = Carbon::now()->startOfMonth();
            //         $endDate = Carbon::now()->endOfMonth();
            //         break;
            //     case 'last_week':
            //         $startDate = Carbon::now()->subWeek()->startOfWeek();
            //         $endDate = Carbon::now()->subWeek()->endOfWeek();
            //         break;
            //     case 'last_month':
            //         $startDate = Carbon::now()->subMonth()->startOfMonth();
            //         $endDate = Carbon::now()->subMonth()->endOfMonth();
            //         break;
            // }

            // Retrieve consumers grouped by day
            $consumersQuery = Consumer::with(['promoter', 'outlet.district'])
                ->when($districtId, function ($query) use ($districtId) {
                    return $query->whereHas('outlet', function ($query) use ($districtId) {
                        $query->where('district_id', $districtId);
                    });
                })
                ->when($campaign_id, function ($query, $campaign_id) {
                    return $query->where('campaign_id', $campaign_id);
                })
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->whereDate('created_at', '>=', $startDate);
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->whereDate('created_at', '<=', $endDate);
                })
                ->get()
                ->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('Y-m-d');
                });

            $reportData = $consumersQuery->map(function ($consumers, $day) {
                $uniquePromoters = $consumers->pluck('user_id')->unique()->count();
                $numberOfVisits = $consumers->count();
                $totalContacts = $numberOfVisits; // Same as number of visits
                $averageContactsPerPromoter = $uniquePromoters > 0 ? $totalContacts / $uniquePromoters : 0;
                $contactEfficiency = ($numberOfVisits * 15) > 0 ? ($totalContacts / ($numberOfVisits * 15)) * 100 : 0;
                $switchersCount = $consumers->where('did_he_switch', true)->count();
                $franchiseCount = $consumers->where('franchise', true)->count();
                $totalEffectiveContacts = $switchersCount + $franchiseCount;
                $averageEffectiveContactsPerPromoter = $uniquePromoters > 0 ? $totalEffectiveContacts / $uniquePromoters : 0;
                $effectiveContactsEfficiency = ($numberOfVisits * 12) > 0 ? ($totalEffectiveContacts / ($numberOfVisits * 12)) * 100 : 0;
                $trialRate = $totalContacts > 0 ? ($totalEffectiveContacts / $totalContacts) * 100 : 0;
                $districtName = $consumers->first()->outlet->district->name ?? 'N/A';
                return [
                    'day' => $day,
                    'district_name' => $districtName,
                    'promoter_count' => $uniquePromoters,
                    'visit_count' => $numberOfVisits,
                    'total_contacts' => $totalContacts,
                    'average_contacts_per_promoter' => $averageContactsPerPromoter,
                    'contact_efficiency' => number_format($contactEfficiency, 2) . '%',
                    'switchers_count' => $switchersCount,
                    'franchise_count' => $franchiseCount,
                    'total_effective_contacts' => $totalEffectiveContacts,
                    'average_effective_contacts_per_promoter' => $averageEffectiveContactsPerPromoter,
                    'effective_contacts_efficiency' => number_format($effectiveContactsEfficiency, 2) . '%',
                    'trial_rate' => number_format($trialRate, 2) . '%',
                ];
            })->sortBy('day');

            return custom_success(200, 'Report generated successfully', $reportData->values());
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function exportPromotersCountByDay(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            // 'period' => 'nullable|string|in:week,month,last_week,last_month',
            'district_id' => 'nullable|integer|exists:districts,id',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
        ]);

        // $period = $request->input('period');
        $districtId = $request->input('district_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $campaign_id = $request->input('campaign_id');

        // switch ($period) {
        //     case 'week':
        //         $startDate = Carbon::now()->startOfWeek();
        //         $endDate = Carbon::now()->endOfWeek();
        //         break;
        //     case 'month':
        //         $startDate = Carbon::now()->startOfMonth();
        //         $endDate = Carbon::now()->endOfMonth();
        //         break;
        //     case 'last_week':
        //         $startDate = Carbon::now()->subWeek()->startOfWeek();
        //         $endDate = Carbon::now()->subWeek()->endOfWeek();
        //         break;
        //     case 'last_month':
        //         $startDate = Carbon::now()->subMonth()->startOfMonth();
        //         $endDate = Carbon::now()->subMonth()->endOfMonth();
        //         break;
        // }

        return Excel::download(new PromotersCountByDayExport($startDate, $endDate, $districtId, $campaign_id), 'promoters_count_by_day_report.xlsx');
    }

    public function promoterDailyFeedback(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_ids' => 'nullable|array',
                'district_ids.*' => 'integer|exists:districts,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            $districtIds = $request->input('district_ids');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $campaign_id = $request->input('campaign_id');

            $attendance_records = AttendanceRecord::with(['user', 'outlet', 'campaign'])
                ->when($campaign_id, function ($query, $campaign_id) {
                    return $query->where('campaign_id', $campaign_id);
                })
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->whereDate('created_at', '>=', $startDate);
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->whereDate('created_at', '<=', $endDate);
                })
                ->when($districtIds, function ($query, $districtIds) {
                    return $query->whereHas('outlet', function ($query) use ($districtIds) {
                        $query->whereIn('district_id', $districtIds);
                    });
                })
                ->get()
                ->groupBy('user.name');

            return custom_success(200, 'Report generated successfully', $attendance_records);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function exportPromoterDailyFeedback(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'district_ids' => 'nullable|array',
                'district_ids.*' => 'integer|exists:districts,id',
                'campaign_id' => 'nullable|integer|exists:campaigns,id',
            ]);

            $districtIds = $request->input('district_ids');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $campaign_id = $request->input('campaign_id');
            return Excel::download(new PromoterDailyFeedback($startDate, $endDate, $districtIds, $campaign_id), 'promoters_daily_feedback_report.xlsx');
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
