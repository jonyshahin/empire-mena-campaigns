<?php

namespace App\Http\Controllers\API;

use App\Enums\IssueStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\IssueItem;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) ($request->per_page ?? 10);

            $models = QueryBuilder::for(Issue::class)
                ->with(['warehouse:id,name,code'])
                ->allowedFilters([
                    'number',
                    AllowedFilter::exact('warehouse_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('issue_date'),
                ])
                ->defaultSort('-created_at')
                ->allowedSorts(['number', 'warehouse_id', 'status', 'issue_date', 'created_at']);

            if ($q = $request->get('q')) {
                $like = '%' . trim($q) . '%';
                $models->where(function ($sub) use ($like) {
                    $sub->where('number', 'like', $like)
                        ->orWhere('remarks', 'like', $like);
                });
            }

            $data = $perPage !== 0 ? $models->paginate($perPage) : $models->get();

            return custom_success(200, 'Issues', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'issue_id' => 'required|integer|exists:issues,id',
            ]);

            $model = Issue::with([
                'warehouse:id,name,code',
                'items.product:id,name,sku,price',
            ])->find($request->issue_id);

            if (!$model) {
                return custom_error(404, 'Issue not found');
            }

            return custom_success(200, 'Issue Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /**
     * Create a DRAFT issue with items[].
     * Body:
     *  - warehouse_id (required)
     *  - issue_date (Y-m-d) optional
     *  - remarks (optional)
     *  - items: [{product_id, quantity, uom?, remarks?}, ...]
     */
    public function store(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to create issues');
            }

            $validator = Validator::make($request->all(), [
                'warehouse_id'       => 'required|integer|exists:warehouses,id',
                'issue_date'         => 'nullable|date',
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

                $issue = Issue::create([
                    'number'       => $number,
                    'warehouse_id' => $request->warehouse_id,
                    'issue_date'   => $request->input('issue_date', now()->toDateString()),
                    'remarks'      => $request->input('remarks'),
                    'status'       => IssueStatus::Draft,
                    'created_by'   => $user->id,
                ]);

                foreach ($request->items as $line) {
                    IssueItem::create([
                        'issue_id'   => $issue->id,
                        'product_id' => $line['product_id'],
                        'quantity'   => $line['quantity'],
                        'uom'        => $line['uom'] ?? null,
                        'remarks'    => $line['remarks'] ?? null,
                    ]);
                }

                return $issue->load(['warehouse:id,name,code', 'items.product:id,name,sku,price']);
            });

            return custom_success(200, 'Issue Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to update issues');
            }

            $validator = Validator::make($request->all(), [
                'issue_id'           => 'required|integer|exists:issues,id',
                'warehouse_id'       => 'nullable|integer|exists:warehouses,id',
                'issue_date'         => 'nullable|date',
                'remarks'            => 'nullable|string',
                'items'              => 'nullable|array|min:1',
                // delete & recreate approach (no items.*.id needed)
                'items.*.product_id' => 'required_with:items|integer|exists:products,id',
                'items.*.quantity'   => 'required_with:items|numeric|min:0.000001',
                'items.*.uom'        => 'nullable|string|max:32',
                'items.*.remarks'    => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $issue = Issue::find($request->issue_id);
            if (!$issue) return custom_error(404, 'Issue not found');
            if (in_array($issue->status, [IssueStatus::Posted, IssueStatus::Canceled], true)) {
                return custom_error(409, 'Cannot update a ' . $issue->status->label() . ' issue');
            }

            DB::transaction(function () use ($request, $issue) {
                $issue->warehouse_id = $request->input('warehouse_id', $issue->warehouse_id);
                $issue->issue_date   = $request->input('issue_date', $issue->issue_date);
                $issue->remarks      = $request->input('remarks', $issue->remarks);
                $issue->save();

                if ($request->filled('items')) {
                    $issue->items()->delete();
                    foreach ($request->items as $line) {
                        IssueItem::create([
                            'issue_id'   => $issue->id,
                            'product_id' => $line['product_id'],
                            'quantity'   => $line['quantity'],
                            'uom'        => $line['uom'] ?? null,
                            'remarks'    => $line['remarks'] ?? null,
                        ]);
                    }
                }
            });

            $issue->load(['warehouse:id,name,code', 'items.product:id,name,sku,price']);

            return custom_success(200, 'Issue Updated Successfully', $issue);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** Move Draft -> Submitted */
    public function submit(Request $request)
    {
        try {
            $request->validate([
                'issue_id' => 'required|integer|exists:issues,id',
            ]);

            $issue = Issue::with('items')->find($request->issue_id);
            if (!$issue) return custom_error(404, 'Issue not found');
            if ($issue->status !== IssueStatus::Draft) {
                return custom_error(409, 'Only Draft issues can be submitted');
            }
            if ($issue->items->isEmpty()) {
                return custom_error(422, 'Issue must have at least one item to submit');
            }

            $issue->status = IssueStatus::Submitted;
            $issue->save();

            return custom_success(200, 'Issue Submitted', $issue);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** POST the issue (create OUT movements & update stock) */
    public function post(Request $request, InventoryService $svc)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to post issues');
            }

            $request->validate([
                'issue_id' => 'required|integer|exists:issues,id',
            ]);

            $issue = Issue::find($request->issue_id);
            if (!$issue) return custom_error(404, 'Issue not found');
            if ($issue->status !== IssueStatus::Submitted) {
                return custom_error(409, 'Only Submitted issues can be posted');
            }

            $posted = $svc->postIssue($issue);

            return custom_success(200, 'Issue Posted Successfully', $posted);
        } catch (InsufficientStockException $e) {
            return custom_error(422, $e->getMessage()); // User-friendly
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** Cancel (only Draft/Submitted, not Posted) */
    public function cancel(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to cancel issues');
            }

            $request->validate([
                'issue_id' => 'required|integer|exists:issues,id',
            ]);

            $issue = Issue::find($request->issue_id);
            if (!$issue) return custom_error(404, 'Issue not found');
            if ($issue->status === IssueStatus::Posted) {
                return custom_error(409, 'Cannot cancel a Posted issue');
            }
            if ($issue->status === IssueStatus::Canceled) {
                return custom_success(200, 'Issue already canceled', $issue);
            }

            $issue->status = IssueStatus::Canceled;
            $issue->save();

            return custom_success(200, 'Issue Canceled', $issue);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** Hard delete only if Draft (optional) */
    public function destroy(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user || !$user->hasRole(['admin'])) {
                return custom_error(403, 'You are not authorized to delete issues');
            }

            $request->validate([
                'issue_id' => 'required|integer|exists:issues,id',
            ]);

            $issue = Issue::find($request->issue_id);
            if (!$issue) return custom_error(404, 'Issue not found');
            if ($issue->status !== IssueStatus::Draft) {
                return custom_error(409, 'Only Draft issues can be deleted');
            }

            $issue->items()->delete();
            $issue->delete();

            return custom_success(200, 'Issue Deleted', []);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /** Simple numbering: GIN-YYYY-#### (improve later with counters) */
    protected function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = Issue::whereYear('created_at', $year)->max('id') ?? 0;
        $seq  = str_pad((string)($last + 1), 4, '0', STR_PAD_LEFT);
        return "GIN-{$year}-{$seq}";
    }
}
