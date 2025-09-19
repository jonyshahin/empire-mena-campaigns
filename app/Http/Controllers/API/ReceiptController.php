<?php

namespace App\Http\Controllers\API;

use App\Enums\ReceiptStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) ($request->per_page ?? 10);

            $models = QueryBuilder::for(Receipt::class)
                ->with(['warehouse:id,name,code'])
                ->allowedFilters([
                    'number',
                    AllowedFilter::exact('warehouse_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('receipt_date'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts(['number', 'warehouse_id', 'status', 'receipt_date', 'created_at']);

            if ($q = $request->get('q')) {
                $like = '%' . trim($q) . '%';
                $models->where(function ($sub) use ($like) {
                    $sub->where('number', 'like', $like)
                        ->orWhere('remarks', 'like', $like);
                });
            }

            $data = $perPage !== 0 ? $models->paginate($perPage) : $models->get();

            return custom_success(200, 'Receipts', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'receipt_id' => 'required|integer|exists:receipts,id',
            ]);

            $model = Receipt::with([
                'warehouse:id,name,code',
                'items.product:id,name,sku,price',
            ])->find($request->receipt_id);

            if (!$model) {
                return custom_error(404, 'Receipt not found');
            }

            return custom_success(200, 'Receipt Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to create receipts');
            }

            $validator = Validator::make($request->all(), [
                'warehouse_id'       => 'required|integer|exists:warehouses,id',
                'receipt_date'       => 'nullable|date',
                'remarks'            => 'nullable|string',
                'items'              => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity'   => 'required|numeric|min:0.000001',
                'items.*.uom'        => 'nullable|string|max:32',
                'items.*.remarks'    => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = DB::transaction(function () use ($request, $user) {
                $number = $this->generateNumber();

                $receipt = Receipt::create([
                    'number'       => $number,
                    'warehouse_id' => $request->warehouse_id,
                    'receipt_date' => $request->input('receipt_date', now()->toDateString()),
                    'remarks'      => $request->input('remarks'),
                    'status'       => ReceiptStatus::Draft,
                    'created_by'   => $user->id,
                ]);

                foreach ($request->items as $line) {
                    ReceiptItem::create([
                        'receipt_id' => $receipt->id,
                        'product_id' => $line['product_id'],
                        'quantity'   => $line['quantity'],
                        'uom'        => $line['uom'] ?? null,
                        'remarks'    => $line['remarks'] ?? null,
                    ]);
                }

                return $receipt->load(['warehouse:id,name,code', 'items.product:id,name,sku,price']);
            });

            return custom_success(200, 'Receipt Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to update receipts');
            }

            $validator = Validator::make($request->all(), [
                'receipt_id'         => 'required|integer|exists:receipts,id',
                'warehouse_id'       => 'nullable|integer|exists:warehouses,id',
                'receipt_date'       => 'nullable|date',
                'remarks'            => 'nullable|string',
                'items'              => 'nullable|array|min:1',
                'items.*.product_id' => 'required_with:items|integer|exists:products,id',
                'items.*.quantity'   => 'required_with:items|numeric|min:0.000001',
                'items.*.uom'        => 'nullable|string|max:32',
                'items.*.remarks'    => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $receipt = Receipt::find($request->receipt_id);
            if (!$receipt) return custom_error(404, 'Receipt not found');
            if (in_array($receipt->status, [ReceiptStatus::Posted, ReceiptStatus::Canceled], true)) {
                return custom_error(409, 'Cannot update a ' . $receipt->status . ' receipt');
            }

            DB::transaction(function () use ($request, $receipt) {
                $receipt->warehouse_id = $request->input('warehouse_id', $receipt->warehouse_id);
                $receipt->receipt_date = $request->input('receipt_date', $receipt->receipt_date);
                $receipt->remarks      = $request->input('remarks', $receipt->remarks);
                $receipt->save();

                if ($request->filled('items')) {
                    // Replace items: easiest is delete & recreate; alternatively upsert by id
                    $receipt->items()->delete();
                    foreach ($request->items as $line) {
                        ReceiptItem::create([
                            'receipt_id' => $receipt->id,
                            'product_id' => $line['product_id'],
                            'quantity'   => $line['quantity'],
                            'uom'        => $line['uom'] ?? null,
                            'remarks'    => $line['remarks'] ?? null,
                        ]);
                    }
                }
            });

            $receipt->load(['warehouse:id,name,code', 'items.product:id,name,sku,price']);

            return custom_success(200, 'Receipt Updated Successfully', $receipt);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function submit(Request $request)
    {
        try {
            $request->validate([
                'receipt_id' => 'required|integer|exists:receipts,id',
            ]);

            $receipt = Receipt::with('items')->find($request->receipt_id);
            if (!$receipt) return custom_error(404, 'Receipt not found');
            if ($receipt->status !== ReceiptStatus::Draft) {
                return custom_error(409, 'Only Draft receipts can be submitted');
            }
            if ($receipt->items->isEmpty()) {
                return custom_error(422, 'Receipt must have at least one item to submit');
            }

            $receipt->status = ReceiptStatus::Submitted;
            $receipt->save();

            return custom_success(200, 'Receipt Submitted', $receipt);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function post(Request $request, InventoryService $svc)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to post receipts');
            }

            $request->validate([
                'receipt_id' => 'required|integer|exists:receipts,id',
            ]);

            $receipt = Receipt::find($request->receipt_id);
            if (!$receipt) return custom_error(404, 'Receipt not found');
            if ($receipt->status !== ReceiptStatus::Submitted) {
                return custom_error(409, 'Only Submitted receipts can be posted');
            }

            $posted = $svc->postReceipt($receipt);

            return custom_success(200, 'Receipt Posted Successfully', $posted);
        } catch (InsufficientStockException $e) {
            return custom_error(422, $e->getMessage()); // User-friendly
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function cancel(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to cancel receipts');
            }

            $request->validate([
                'receipt_id' => 'required|integer|exists:receipts,id',
            ]);

            $receipt = Receipt::find($request->receipt_id);
            if (!$receipt) return custom_error(404, 'Receipt not found');
            if ($receipt->status === ReceiptStatus::Posted) {
                return custom_error(409, 'Cannot cancel a Posted receipt');
            }
            if ($receipt->status === ReceiptStatus::Canceled) {
                return custom_success(200, 'Receipt already canceled', $receipt);
            }

            $receipt->status = ReceiptStatus::Canceled;
            $receipt->save();

            return custom_success(200, 'Receipt Canceled', $receipt);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to delete receipts');
            }

            $request->validate([
                'receipt_id' => 'required|integer|exists:receipts,id',
            ]);

            $receipt = Receipt::find($request->receipt_id);
            if (!$receipt) return custom_error(404, 'Receipt not found');
            if ($receipt->status !== ReceiptStatus::Draft) {
                return custom_error(409, 'Only Draft receipts can be deleted');
            }

            $receipt->items()->delete();
            $receipt->delete();

            return custom_success(200, 'Receipt Deleted', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    protected function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = Receipt::whereYear('created_at', $year)->max('id') ?? 0;
        $seq  = str_pad((string)($last + 1), 4, '0', STR_PAD_LEFT);
        return "GRN-{$year}-{$seq}";
    }
}
