<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Bepsvpt\Blurhash\Facades\BlurHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class ProductCategoryController extends Controller
{
    public function get_all(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $product_categories = QueryBuilder::for(ProductCategory::class)
                ->with(['parent', 'children'])
                ->allowedFilters([
                    'name',
                    'is_active',
                    'is_featured',
                    'status',
                    'parent.name',
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'created_at',
                    'order',
                ])
                ->paginate($per_page);

            $data = [
                'total' => $product_categories->total(),
                'data' => $product_categories->items(),
            ];

            return custom_success(200, 'Product Category List', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function get_product_categories(Request $request)
    {
        try {
            $per_page = $request->perPage ?? 10;

            $product_categories = QueryBuilder::for(ProductCategory::where('parent_id', null))
                ->with(['children', 'parent'])
                ->allowedFilters([
                    'name',
                    'is_active',
                    'is_featured',
                    'status',
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'created_at',
                    'order',
                    'parent_id',
                ])
                ->paginate($per_page);

            $data = [
                'total' => $product_categories->total(),
                'data' => $product_categories->items(),
            ];

            return custom_success(200, 'Product Category List', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|string|unique:product_categories,name',
                    'description' => 'nullable|string',
                    'image' => 'nullable',
                    'icon' => 'nullable',
                    'parent_id' => 'nullable|exists:product_categories,id',
                    'is_active' => 'nullable|boolean',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $product_category = ProductCategory::create([
                'name' => $request->name,
                'parent_id' => $request->input('parent_id'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
            ]);

            if ($request->hasFile('image')) {
                $product_category->addMediaFromRequest('image')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->image)])
                    ->toMediaCollection('image');
            }

            if ($request->hasFile('icon')) {
                $product_category->addMediaFromRequest('icon')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->icon)])
                    ->toMediaCollection('icon');
            }

            $product_category->load(['parent', 'children']);

            return custom_success(200, 'Product Category Created Successfully', $product_category);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function toggle_active(Request $request)
    {
        try {
            $model = ProductCategory::query()->find($request->product_category_id);
            //if user not found
            if (!$model) {
                return custom_error(404, 'Product Category not found');
            }

            $model->is_active = !$model->is_active;
            $model->save();

            $model->load(['parent', 'children']);

            return custom_success(200, 'Product Category toggled successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $product_category = ProductCategory::with(['parent', 'children'])->find($request->product_category_id);
            if (!$product_category) {
                return custom_error(404, 'Product Category Not Found');
            }

            return custom_success(200, 'Product Category Details', $product_category);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product_category = ProductCategory::find($id);
            if (!$product_category) {
                return custom_error(404, 'Product Category Not Found');
            }

            $data = $request->all();
            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|string|unique:product_categories,name,' . $product_category->id,
                    'description' => 'nullable|string',
                    'image' => 'nullable',
                    'icon' => 'nullable',
                    'parent_id' => 'nullable|exists:product_categories,id',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $product_category->name = $request->name;
            $product_category->description = $request->input('description', $product_category->description);
            $product_category->parent_id = $request->input('parent_id');
            $product_category->save();

            if ($request->hasFile('image')) {
                $product_category->addMediaFromRequest('image')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->image)])
                    ->toMediaCollection('image');
            }

            if ($request->hasFile('icon')) {
                $product_category->addMediaFromRequest('icon')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->icon)])
                    ->toMediaCollection('icon');
            }

            $product_category->load(['parent', 'children']);


            return custom_success(200, 'Product Category Updated Successfully', $product_category);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $product_category = ProductCategory::find($request->product_category_id);
            if (!$product_category) {
                return custom_error(404, 'Product Category Not Found');
            }
            //make all children parent_id = $product_category->parent_id
            $children = $product_category->children;
            foreach ($children as $child) {
                $child->parent_id = $product_category->parent_id;
                $child->save();
            }
            //delete product category
            $product_category->delete();

            return custom_success(200, 'Product Category Deleted Successfully', $product_category);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
