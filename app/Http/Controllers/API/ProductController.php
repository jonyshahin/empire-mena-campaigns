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

            //check if images array is not empty add media to media collection
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
}
