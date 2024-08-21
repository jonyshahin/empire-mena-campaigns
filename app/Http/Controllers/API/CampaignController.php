<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $models = QueryBuilder::for(Campaign::class)
                ->allowedFilters([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    AllowedFilter::exact('client_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'budget',
                    'client_id',
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
                    'client_id' => 'nullable|exists:clients,id',
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
                'budget' => $request->input('budget'),
                'client_id' => $request->input('client_id'),
            ]);

            return custom_success(200, 'Campaign Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $model = Campaign::find($request->campaign_id);
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
                    'client_id' => 'nullable|exists:clients,id',
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
            $model->client_id = $request->input('client_id', $model->client_id);
            $model->save();

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
