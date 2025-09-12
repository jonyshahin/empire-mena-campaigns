<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) ($request->per_page ?? 10);

            $user = User::find(Auth::id());

            $models = QueryBuilder::for(Warehouse::class)
                ->allowedFilters([
                    'name',
                    'code',
                    'location',
                    AllowedFilter::exact('is_active'),
                    AllowedFilter::exact('district_id'),
                    AllowedFilter::exact('zone_id'),
                    AllowedFilter::exact('manager_id'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'name',
                    'code',
                    'location',
                    'is_active',
                    'district_id',
                    'zone_id',
                    'manager_id',
                    'created_at',
                ]);

            // Simple free-text search (q)
            if ($q = $request->get('q')) {
                $models->where(function ($query) use ($q) {
                    $like = '%' . trim($q) . '%';
                    $query->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            }

            // Pagination toggle
            $data = $perPage !== 0 ? $models->paginate($perPage) : $models->get();

            return custom_success(200, 'Warehouse List', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            $model = Warehouse::query()
                ->with(['district', 'zone', 'manager'])
                ->find($request->warehouse_id);

            if (!$model) {
                return custom_error(404, 'Warehouse Not Found');
            }

            return custom_success(200, 'Warehouse Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['super-admin', 'admin'])) {
                return custom_error(403, 'You are not authorized to create warehouses');
            }

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:255',
                'code'        => 'required|string|max:50|unique:warehouses,code',
                'location'    => 'nullable|string|max:255',
                'manager_id'  => 'nullable|exists:users,id',
                'is_active'   => 'nullable|boolean',
                'district_id' => 'required|integer|exists:districts,id',
                'zone_id'     => [
                    'required',
                    'integer',
                    'exists:zones,id',
                    function ($attr, $value, $fail) use ($request) {
                        $zone = Zone::find($value);
                        if (!$zone || (int)$zone->district_id !== (int)$request->district_id) {
                            $fail('The selected zone does not belong to the chosen district.');
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = Warehouse::create([
                'name'        => $request->name,
                'code'        => $request->code,
                'location'    => $request->input('location'),
                'manager_id'  => $request->input('manager_id'),
                'is_active'   => $request->boolean('is_active', true),
                'district_id' => $request->district_id,
                'zone_id'     => $request->zone_id,
            ]);

            $model->load(['district', 'zone', 'manager']);

            return custom_success(200, 'Warehouse Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['super-admin', 'admin'])) {
                return custom_error(403, 'You are not authorized to update warehouses');
            }

            $model = Warehouse::find($request->warehouse_id);
            if (!$model) {
                return custom_error(404, 'Warehouse Not Found');
            }

            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'name'        => 'nullable|string|max:255',
                'code'        => 'nullable|string|max:50|unique:warehouses,code,' . $model->id,
                'location'    => 'nullable|string|max:255',
                'manager_id'  => 'nullable|exists:users,id',
                'is_active'   => 'nullable|boolean',
                'district_id' => 'nullable|integer|exists:districts,id',
                'zone_id'     => [
                    'nullable',
                    'integer',
                    'exists:zones,id',
                    function ($attr, $value, $fail) use ($request, $model) {
                        if (!$value) return;
                        $districtId = $request->input('district_id', $model->district_id);
                        $zone = Zone::find($value);
                        if (!$zone || (int)$zone->district_id !== (int)$districtId) {
                            $fail('The selected zone does not belong to the chosen district.');
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model->name        = $request->input('name', $model->name);
            $model->code        = $request->input('code', $model->code);
            $model->location    = $request->input('location', $model->location);
            $model->manager_id  = $request->input('manager_id', $model->manager_id);
            $model->is_active   = $request->has('is_active') ? $request->boolean('is_active') : $model->is_active;
            $model->district_id = $request->input('district_id', $model->district_id);
            $model->zone_id     = $request->input('zone_id', $model->zone_id);
            $model->save();

            $model->load(['district', 'zone', 'manager']);

            return custom_success(200, 'Warehouse Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['super-admin', 'admin'])) {
                return custom_error(403, 'You are not authorized to delete warehouses');
            }

            $request->validate([
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            $model = Warehouse::find($request->warehouse_id);
            if (!$model) {
                return custom_error(404, 'Warehouse Not Found');
            }

            $model->delete();

            return custom_success(200, 'Warehouse Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function items(Request $request)
    {
        try {
            $request->validate([
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'per_page'     => 'nullable|integer',
            ]);

            $perPage = (int) ($request->per_page ?? 10);

            $warehouse = Warehouse::find($request->warehouse_id);
            if (!$warehouse) {
                return custom_error(404, 'Warehouse Not Found');
            }

            $items = $warehouse->items()->with('product:id,name,sku,price')
                ->orderBy('id', 'desc');

            $data = $perPage !== 0 ? $items->paginate($perPage) : $items->get();

            return custom_success(200, 'Warehouse Items', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function movements(Request $request)
    {
        try {
            $request->validate([
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'type'         => 'nullable|string',
                'from'         => 'nullable|date',
                'to'           => 'nullable|date',
                'per_page'     => 'nullable|integer',
            ]);

            $perPage = (int) ($request->per_page ?? 10);

            $warehouse = Warehouse::find($request->warehouse_id);
            if (!$warehouse) {
                return custom_error(404, 'Warehouse Not Found');
            }

            $movs = $warehouse->movements()
                ->with(['product:id,name,sku', 'author:id,name'])
                ->when($request->filled('type'), fn($q) => $q->where('movement_type', $request->type))
                ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->filled('to'), fn($q) => $q->whereDate('created_at', '<=', $request->to))
                ->orderBy('created_at', 'desc');

            $data = $perPage !== 0 ? $movs->paginate($perPage) : $movs->get();

            return custom_success(200, 'Warehouse Movements', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
