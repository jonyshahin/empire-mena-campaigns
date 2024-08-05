<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompetitorBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompetitorBrandController extends Controller
{
    public function index(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;
            $search = $request->search ?? '';
            $competitorBrands = CompetitorBrand::query();
            if ($search = '') {
                $competitorBrands->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%$search%");
                });
            }
            if ($request->perPage == 0) {
                $competitorBrands = $competitorBrands->get();
            } else {
                $competitorBrands = $competitorBrands->paginate($per_page);
            }
            return custom_success(200, 'CompetitorBrand List', $competitorBrands);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function create_competitor_brand(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|string',
                    'description' => 'nullable|string',
                    'logo' => 'nullable',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $competitor_brand = CompetitorBrand::create([
                'name' => $request->name,
                'description' => $request->input('description'),
            ]);

            if ($request->hasFile('logo')) {
                $competitor_brand->addMediaFromRequest('logo')->toMediaCollection('logo');
            }

            return custom_success(200, 'CompetitorBrand Created Successfully', $competitor_brand);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function get_brand(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'id' => 'required|integer',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }
            $id = $request->id;
            $brand = CompetitorBrand::find($id);
            if (!$brand) {
                return custom_error(404, 'Brand Not Found');
            }

            return custom_success(200, 'Brand Details', $brand);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update_brand(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'id' => 'required|integer',
                    'name' => 'nullable|string',
                    'description' => 'nullable|string',
                    'logo' => 'nullable',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $id = $request->id;
            $brand = CompetitorBrand::find($id);
            if (!$brand) {
                return custom_error(404, 'Brand Not Found');
            }

            $brand->name = $request->input('name', $brand->name);
            $brand->description = $request->input('description', $brand->description);
            $brand->save();

            if ($request->hasFile('logo')) {
                $brand->addMediaFromRequest('logo')->toMediaCollection('logo');
            }

            return custom_success(200, 'Brand Updated Successfully', $brand);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function delete_brand(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'id' => 'required|integer',
                ]
            );
            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }
            $id = $request->id;
            $brand = CompetitorBrand::find($id);
            if (!$brand) {
                return custom_error(404, 'Brand Not Found');
            }

            $brand->delete();

            return custom_success(200, 'Brand Deleted Successfully', $brand);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
