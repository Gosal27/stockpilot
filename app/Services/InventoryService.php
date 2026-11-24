<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function stockIn(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah stok masuk harus lebih dari 0.');
        }

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $notes,
            $referenceType,
            $referenceId
        ) {
            // Pastikan record stok tersedia.
            Stock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                ],
                [
                    'quantity' => 0,
                ]
            );

            // Kunci record selama transaksi berlangsung.
            $stock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $stockBefore = $stock->quantity;
            $stockAfter = $stockBefore + $quantity;

            $stock->update([
                'quantity' => $stockAfter,
            ]);

            StockMovement::create([
                'stock_id' => $stock->id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'IN',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => auth::id(),
                'created_at' => now(),
            ]);

            return $stock->fresh();
        });
    }

    public function stockOut(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah stok keluar harus lebih dari 0.');
        }

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $stock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new RuntimeException('Stok produk di gudang ini belum tersedia.');
            }

            $stockBefore = $stock->quantity;

            if ($stockBefore < $quantity) {
                throw new RuntimeException('Stok tidak mencukupi.');
            }

            $stockAfter = $stockBefore - $quantity;

            $stock->update([
                'quantity' => $stockAfter,
            ]);

            StockMovement::create([
                'stock_id' => $stock->id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'OUT',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => auth::id(),
                'created_at' => now(),
            ]);

            return $stock->fresh();
        });
    }

}
