<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\StockMovement;
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
                throw new \DomainException('Insufficient stock for product_id=' . $productId . ' in warehouse_id=' . $warehouseId);
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
}
