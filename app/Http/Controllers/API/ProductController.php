<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

            $product_categories = QueryBuilder::for(ProductCategory::class)
                ->with(['parent', 'children'])
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
}
