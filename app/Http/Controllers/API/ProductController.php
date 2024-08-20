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
        $products = Product::all();

        return response()->json([
            'success' => true,
            'data' => $products,
        ], Response::HTTP_OK);
        try {
            $per_page = $request->perPage ?? 10;

            $product_categories = QueryBuilder::for(Product::class)
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
                'parent_id' => $request->input('parent_id'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
            ]);

            if ($request->hasFile('main_image')) {
                $model->addMediaFromRequest('main_image')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->image)])
                    ->toMediaCollection('main_image');
            }

            if ($request->hasFile('icon')) {
                $model->addMediaFromRequest('images')
                    ->withCustomProperties(['hash' => BlurHash::encode($request->icon)])
                    ->toMediaCollection('images');
            }

            return custom_success(200, 'Product Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
