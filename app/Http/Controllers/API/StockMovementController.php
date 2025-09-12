<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int)($request->per_page ?? 10);
            $user = User::find(Auth::id());

            // Base query with relations
            $models = QueryBuilder::for(StockMovement::class)
                ->with([
                    'warehouse:id,name,code',
                    'product:id,name,sku,price',
                    'author:id,name',
                ])
                ->allowedFilters([
                    AllowedFilter::exact('warehouse_id'),
                    AllowedFilter::exact('product_id'),
                    AllowedFilter::exact('movement_type'), // integer (enum backing)
                    AllowedFilter::exact('reference_type'),
                    AllowedFilter::exact('reference_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'created_at',
                    'quantity',
                ]);

            // Free-text search (product name/sku or remarks)
            if ($q = $request->get('q')) {
                $like = '%' . trim($q) . '%';
                $models->where(function ($sub) use ($like) {
                    $sub->whereHas('product', function ($p) use ($like) {
                        $p->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like);
                    })->orWhere('remarks', 'like', $like);
                });
            }

            // Date range
            $models->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->filled('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->to));

            $data = $perPage !== 0 ? $models->paginate($perPage) : $models->get();

            return custom_success(200, 'Stock Movements', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'stock_movement_id' => 'required|integer|exists:stock_movements,id',
            ]);

            $model = StockMovement::with([
                'warehouse:id,name,code',
                'product:id,name,sku,price',
                'author:id,name',
                'reference', // morph
            ])->find($request->stock_movement_id);

            if (!$model) {
                return custom_error(404, 'Stock Movement Not Found');
            }

            return custom_success(200, 'Stock Movement Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /* =======================
     | Disabled mutations
     |======================= */

    public function store()
    {
        return custom_error(405, 'Movements are created by posting documents');
    }
    public function update()
    {
        return custom_error(405, 'Movements are updated via their source documents');
    }
    public function destroy()
    {
        return custom_error(405, 'Movements can only be voided by reversing documents');
    }
}
