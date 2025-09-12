<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseItemController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int)($request->per_page ?? 10);

            $user = User::find(Auth::id());
            // Optional: restrict by role if needed
            // if (!$user->hasRole(['admin','team_leader'])) { ... }

            $models = QueryBuilder::for(WarehouseItem::class)
                ->with(['warehouse:id,name,code', 'product:id,name,sku,price'])
                ->allowedFilters([
                    AllowedFilter::exact('warehouse_id'),
                    AllowedFilter::exact('product_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'on_hand',
                    'reserved',
                    'created_at',
                ]);

            // free-text search on related product fields
            if ($q = $request->get('q')) {
                $like = '%' . trim($q) . '%';
                $models->whereHas('product', function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                });
            }

            $data = $perPage !== 0 ? $models->paginate($perPage) : $models->get();

            return custom_success(200, 'Warehouse Items', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'warehouse_item_id' => 'required|integer|exists:warehouse_items,id',
            ]);

            $model = WarehouseItem::with(['warehouse:id,name,code', 'product:id,name,sku,price'])
                ->find($request->warehouse_item_id);

            if (!$model) {
                return custom_error(404, 'Warehouse Item Not Found');
            }

            return custom_success(200, 'Warehouse Item Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to create warehouse items');
            }

            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'product_id'   => 'required|integer|exists:products,id',
                'on_hand'      => 'nullable|numeric|min:0',
                'reserved'     => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            // Enforce unique (warehouse_id, product_id)
            $exists = WarehouseItem::where('warehouse_id', $request->warehouse_id)
                ->where('product_id', $request->product_id)
                ->exists();
            if ($exists) {
                return custom_error(422, 'This product already exists in the selected warehouse');
            }

            $model = WarehouseItem::create([
                'warehouse_id' => $request->warehouse_id,
                'product_id'   => $request->product_id,
                'on_hand'      => $request->input('on_hand', 0),
                'reserved'     => $request->input('reserved', 0),
            ])->load(['warehouse:id,name,code', 'product:id,name,sku,price']);

            return custom_success(200, 'Warehouse Item Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to update warehouse items');
            }

            $validator = Validator::make($request->all(), [
                'warehouse_item_id' => 'required|integer|exists:warehouse_items,id',
                'on_hand'           => 'nullable|numeric|min:0',
                'reserved'          => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = WarehouseItem::find($request->warehouse_item_id);
            if (!$model) {
                return custom_error(404, 'Warehouse Item Not Found');
            }

            // Optional: prevent reserved > on_hand
            if ($request->filled('reserved')) {
                $newReserved = (float)$request->reserved;
                $newOnHand = $request->filled('on_hand') ? (float)$request->on_hand : (float)$model->on_hand;
                if ($newReserved > $newOnHand) {
                    return custom_error(422, 'Reserved quantity cannot exceed on hand quantity');
                }
            }

            $model->on_hand  = $request->input('on_hand', $model->on_hand);
            $model->reserved = $request->input('reserved', $model->reserved);
            $model->save();

            $model->load(['warehouse:id,name,code', 'product:id,name,sku,price']);

            return custom_success(200, 'Warehouse Item Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to delete warehouse items');
            }

            $request->validate([
                'warehouse_item_id' => 'required|integer|exists:warehouse_items,id',
            ]);

            $model = WarehouseItem::find($request->warehouse_item_id);
            if (!$model) {
                return custom_error(404, 'Warehouse Item Not Found');
            }

            $model->delete();

            return custom_success(200, 'Warehouse Item Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
