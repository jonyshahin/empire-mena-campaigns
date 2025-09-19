<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Enums\MovementType;
use App\Enums\ReceiptStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Issue;
use App\Models\IssueItem;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Generic adjust helper: updates warehouse_items and logs a stock movement.
     * $qty must be a positive value; direction is inferred from $type.
     */
    public function adjust(
        int $warehouseId,
        int $productId,
        MovementType $type,
        float $qty,
        Model $reference,
        ?string $uom = null,
        ?string $remarks = null
    ): void {
        DB::transaction(function () use ($warehouseId, $productId, $type, $qty, $reference, $uom, $remarks) {
            // 1) lock row (or create)
            $wi = WarehouseItem::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                    ['on_hand' => 0, 'reserved' => 0]
                );

            // 2) compute delta
            $delta = $type->isInbound() ? +$qty : -$qty;

            // 3) guard against negative stock
            if (($wi->on_hand + $delta) < 0) {
                $product   = Product::find($productId);
                $warehouse = Warehouse::find($warehouseId);
                // throw new \DomainException('Insufficient stock for product_id=' . $productId . ' in warehouse_id=' . $warehouseId);
                throw new InsufficientStockException(
                    "Insufficient stock of '{$product?->name}' in warehouse '{$warehouse?->name}'. " .
                        "Available: {$wi->on_hand}, Requested: {$qty}."
                );
            }

            // 4) persist new quantity
            $wi->on_hand = $wi->on_hand + $delta;
            $wi->save();

            // 5) movement log
            StockMovement::create([
                'warehouse_id'   => $warehouseId,
                'product_id'     => $productId,
                'movement_type'  => $type,     // auto-casts to int via enum
                'quantity'       => $qty,      // store positive
                'uom'            => $uom,
                'reference_type' => get_class($reference),
                'reference_id'   => $reference->getKey(),
                'remarks'        => $remarks,
                'created_by'     => Auth::id(),
            ]);
        });
    }

    /**
     * Post (apply) a receipt: IN movements for each item.
     */
    public function postReceipt(Receipt $receipt): Receipt
    {
        if ($receipt->status !== ReceiptStatus::Submitted) {
            throw new \DomainException('Receipt must be in Submitted state to post.');
        }

        DB::transaction(function () use ($receipt) {
            $receipt->loadMissing('items');

            foreach ($receipt->items as $line) {
                /** @var ReceiptItem $line */
                $this->adjust(
                    warehouseId: $receipt->warehouse_id,
                    productId: $line->product_id,
                    type: MovementType::IN,
                    qty: (float)$line->quantity,
                    reference: $line,
                    uom: $line->uom,
                    remarks: 'Receipt posted: ' . $receipt->number
                );
            }

            $receipt->update([
                'status'    => ReceiptStatus::Posted,
                'posted_at' => now(),
            ]);
        });

        return $receipt->fresh(['items', 'warehouse']);
    }

    /**
     * Post (apply) an Issue: OUT movements for each item.
     * Decrements on_hand and writes stock_movements.
     */
    public function postIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::Submitted) {
            throw new \DomainException('Issue must be in Submitted state to post.');
        }

        DB::transaction(function () use ($issue) {
            $issue->loadMissing('items');

            foreach ($issue->items as $line) {
                /** @var IssueItem $line */
                $this->adjust(
                    warehouseId: $issue->warehouse_id,
                    productId: $line->product_id,
                    type: MovementType::OUT,
                    qty: (float)$line->quantity,
                    reference: $line,
                    uom: $line->uom,
                    remarks: 'Issue posted: ' . $issue->number
                );
            }

            $issue->update([
                'status'    => IssueStatus::Posted,
                'posted_at' => now(),
            ]);
        });

        return $issue->fresh(['items', 'warehouse']);
    }
}
