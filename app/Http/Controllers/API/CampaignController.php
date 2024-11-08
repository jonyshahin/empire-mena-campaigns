<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $user = Auth::user();
            $user = User::find($user->id);

            $models = QueryBuilder::for(Campaign::class);

            if ($user->hasRole('promoter')) {
                $campaigns = $user->campaigns()->pluck('campaign_id');
                $models->whereIn('id', $campaigns);
            }
            if ($user->hasRole('team_leader')) {
                $campaigns = $user->team_leader_campaigns()->pluck('campaign_id');
                $models->whereIn('id', $campaigns);
            }
            $per_page = $request->perPage ?? 10;


            $models->allowedFilters([
                'name',
                'description',
                'start_date',
                'end_date',
                'budget',
                AllowedFilter::exact('company_id'),
                'target',
                'effective_contact_target',
            ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'company_id',
                    'target',
                    'effective_contact_target',
                    'created_at'
                ]);
            if ($per_page != 0) {
                $models = $models->paginate($per_page);
            } else {
                $models = $models->get();
            }

            return custom_success(200, 'Campaign List', $models);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function client_campaigns(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $user = Auth::user();
            $user = User::find($user->id);

            $company = $user->company;

            $models = QueryBuilder::for(Campaign::class)
                ->where('company_id', $company->id)
                ->allowedFilters([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'target',
                    'effective_contact_target',
                    AllowedFilter::exact('company_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'company_id',
                    'target',
                    'effective_contact_target',
                    'created_at'
                ])
                ->paginate($per_page);

            return custom_success(200, 'Campaign List', $models);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function promoter_campaigns(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $user = Auth::user();
            $user = User::find($user->id);

            //check if user has role promoter
            if (!$user->hasRole('promoter')) {
                return custom_error(403, 'You are not authorized to access this resource');
            }

            $campaigns = $user->campaigns()->pluck('campaign_id');

            $models = QueryBuilder::for(Campaign::class)
                ->whereIn('id', $campaigns)
                ->allowedFilters([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'target',
                    'effective_contact_target',
                    AllowedFilter::exact('company_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'target',
                    'effective_contact_target',
                    'company_id',
                    'created_at'
                ])
                ->paginate($per_page);

            return custom_success(200, 'Campaign List', $models);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function team_leader_campaigns(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $user = Auth::user();
            $user = User::find($user->id);

            //check if user has role promoter
            if (!$user->hasRole('team_leader')) {
                return custom_error(403, 'You are not authorized to access this resource');
            }

            $campaigns = $user->team_leader_campaigns()->pluck('campaign_id');

            $models = QueryBuilder::for(Campaign::class)
                ->whereIn('id', $campaigns)
                ->allowedFilters([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'target',
                    'effective_contact_target',
                    AllowedFilter::exact('company_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'target',
                    'effective_contact_target',
                    'company_id',
                    'created_at'
                ])
                ->paginate($per_page);

            return custom_success(200, 'Campaign List', $models);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string',
                    'description' => 'nullable|string',
                    'start_date' => 'nullable|date',
                    'end_date' => 'nullable|date',
                    'budget' => 'nullable|numeric',
                    'company_id' => 'nullable|exists:clients,id',
                    'product_ids' => 'nullable|array|exists:products,id',
                    'promoter_ids' => 'nullable|array|exists:users,id',
                    'team_leader_ids' => 'nullable|array|exists:users,id',
                    'competitor_product_ids' => 'nullable|array|exists:products,id',
                    'target' => 'nullable|integer',
                    'effective_contact_target' => 'nullable|integer',
                    'campaign_settings' => 'nullable|array',
                    'campaign_settings.*.id' => 'required|integer|exists:settings,id',
                    'campaign_settings.*.value' => 'required',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = Campaign::create([
                'name' => $request->name,
                'description' => $request->input('description'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'budget' => isset($request->budget) ? $request->budget : 0.00,
                'company_id' => $request->input('company_id'),
                'target' => $request->input('target', 1),
                'effective_contact_target' => $request->input('effective_contact_target', 1),
            ]);

            if ($request->has('product_ids')) {
                $model->products()->sync($request->product_ids);
            }

            if ($request->has('competitor_product_ids')) {
                $model->competitor_products()->sync($request->competitor_product_ids);
            }

            if ($request->has('promoter_ids')) {
                $model->promoters()->sync($request->promoter_ids);
            }

            if ($request->has('team_leader_ids')) {
                $model->team_leaders()->sync($request->team_leader_ids);
            }

            if (isset($request->campaign_settings)) {
                foreach ($request->campaign_settings as $setting) {
                    $model->settings()->syncWithoutDetaching($setting->id, ['value' => $setting->value]);
                }
            }

            $model = Campaign::find($model->id);

            return custom_success(200, 'Campaign Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $model = Campaign::query();

            $user = Auth::user();
            $user = User::find($user->id);

            if ($user->hasRole('client')) {
                $company = $user->company;
                $model->where('company_id', $company->id);
            }

            $model = $model->find($request->campaign_id);
            if (!$model) {
                return custom_error(404, 'Campaign Not Found');
            }

            return custom_success(200, 'Campaign Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $model = Campaign::find($request->campaign_id);
            if (!$model) {
                return custom_error(404, 'Campaign Not Found');
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'nullable|string',
                    'description' => 'nullable|string',
                    'start_date' => 'nullable|date',
                    'end_date' => 'nullable|date',
                    'budget' => 'nullable|numeric',
                    'company_id' => 'nullable|exists:clients,id',
                    'product_ids' => 'nullable|array|exists:products,id',
                    'promoter_ids' => 'nullable|array|exists:users,id',
                    'team_leader_ids' => 'nullable|array|exists:users,id',
                    'competitor_product_ids' => 'nullable|array|exists:products,id',
                    'target' => 'nullable|integer',
                    'effective_contact_target' => 'nullable|integer',
                    'settings' => 'nullable|array',
                    'settings.*.setting_id' => 'required|integer|exists:settings,id',
                    'settings.*.value' => 'required',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model->name = $request->input('name', $model->name);
            $model->description = $request->input('description', $model->description);
            $model->start_date = $request->input('start_date', $model->start_date);
            $model->end_date = $request->input('end_date', $model->end_date);
            $model->budget = $request->input('budget', $model->budget);
            $model->company_id = $request->input('company_id', $model->company_id);
            $model->target = $request->input('target', $model->target);
            $model->effective_contact_target = $request->input('effective_contact_target', $model->effective_contact_target);
            $model->save();

            if ($request->has('product_ids')) {
                $model->products()->sync($request->product_ids);
            }

            if ($request->has('competitor_product_ids')) {
                $model->competitor_products()->sync($request->competitor_product_ids);
            }

            if ($request->has('promoter_ids')) {
                $model->promoters()->sync($request->promoter_ids);
            }

            if ($request->has('team_leader_ids')) {
                $model->team_leaders()->sync($request->team_leader_ids);
            }

            if (isset($request->settings)) {
                $model->settings()->sync($request->settings);
            }

            $model = Campaign::find($request->campaign_id);

            return custom_success(200, 'Campaign Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $model = Campaign::find($request->campaign_id);
            if (!$model) {
                return custom_error(404, 'Campaign Not Found');
            }
            $model->delete();

            return custom_success(200, 'Campaign Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
