<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class IndustryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $models = QueryBuilder::for(Industry::class)
                ->allowedFilters([
                    'name',
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'created_at'
                ]);
            if ($request->perPage == 0) {
                $models = $models->get();
            } else {
                $models = $models->paginate($per_page);
            }

            return custom_success(200, 'Industry List', $models);
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
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = Industry::create([
                'name' => $request->name,
            ]);

            return custom_success(200, 'Industry Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $model = Industry::find($request->industry_id);
            if (!$model) {
                return custom_error(404, 'Industry Not Found');
            }

            return custom_success(200, 'Industry Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $model = Industry::find($request->industry_id);
            if (!$model) {
                return custom_error(404, 'Industry Not Found');
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'nullable|string',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model->name = $request->input('name', $model->name);
            $model->save();

            return custom_success(200, 'Industry Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $model = Industry::find($request->industry_id);
            if (!$model) {
                return custom_error(404, 'Industry Not Found');
            }
            $model->delete();

            return custom_success(200, 'Industry Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
