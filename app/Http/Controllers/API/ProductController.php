<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Bepsvpt\Blurhash\Facades\BlurHash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $per_page = $request->perPage ?? 10;

            $models = QueryBuilder::for(Product::class)
                ->allowedFilters([
                    'name',
                    'is_active',
                    'parent.name',
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'created_at',
                    'order',
                ])
                ->paginate($per_page);

            return custom_success(200, 'Product List', $models);
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
                    'name' => 'required|string|unique:products,name',
                    'description' => 'nullable|string',
                    'price' => 'nullable|numeric',
                    'stock' => 'nullable|integer',
                    'sku' => 'nullable|string|unique:products,sku',
                    'product_category_id' => 'nullable|exists:product_categories,id',
                    'main_image' => 'nullable',
                    'images' => 'nullable|array',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = Product::create([
                'name' => $request->name,
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'stock' => $request->input('stock'),
                'sku' => $request->input('sku'),
                'product_category_id' => $request->input('product_category_id'),
            ]);

            if ($request->hasFile('main_image')) {
                $model->addMediaFromRequest('main_image')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->image)])
                    ->toMediaCollection('main_image');
            }

            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    $model->addMedia($image)
                        ->withCustomProperties(['hash' => BlurHash::encode($image)])
                        ->toMediaCollection('images');
                }
            }

            return custom_success(200, 'Product Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $model = Product::find($request->product_id);
            if (!$model) {
                return custom_error(404, 'Product Not Found');
            }

            return custom_success(200, 'Product Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $model = Product::find($request->product_id);
            if (!$model) {
                return custom_error(404, 'Product Not Found');
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|unique:products,name,' . $model->id,
                    'description' => 'nullable|string',
                    'price' => 'nullable|numeric',
                    'stock' => 'nullable|integer',
                    'sku' => 'nullable|string|unique:products,sku,' . $model->id,
                    'product_category_id' => 'nullable|exists:product_categories,id',
                    'main_image' => 'nullable',
                    'images' => 'nullable|array',
                    'deleted_image_ids' => 'nullable|array',
                    'deleted_image_ids.*' => 'nullable|exists:media,id'
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model->name = $request->name;
            $model->description = $request->input('description', $model->description);
            $model->price = $request->input('price', $model->price);
            $model->stock = $request->input('stock', $model->stock);
            $model->sku = $request->input('sku', $model->sku);
            $model->product_category_id = $request->input('product_category_id', $model->product_category_id);
            $model->save();

            if ($request->hasFile('main_image')) {
                $model->addMediaFromRequest('main_image')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->image)])
                    ->toMediaCollection('main_image');
            }

            //Delete extra images
            if ($request->filled('deleted_image_ids')) {
                foreach ($request->deleted_image_ids as $deleted_image_id) {
                    $model->deleteMedia($deleted_image_id);
                }
            }
            //end delete extra images

            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    $model->addMedia($image)
                        ->withCustomProperties(['hash' => BlurHash::encode($image)])
                        ->toMediaCollection('images');
                }
            }

            return custom_success(200, 'Product Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $model = Product::find($request->product_id);
            if (!$model) {
                return custom_error(404, 'Product Not Found');
            }
            //delete product category
            $model->delete();

            return custom_success(200, 'Product Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
