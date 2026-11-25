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
    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $notes = null
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                'Jumlah transfer harus lebih dari 0.'
            );
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException(
                'Gudang asal dan tujuan tidak boleh sama.'
            );
        }

        DB::transaction(function () use (
            $productId,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            $notes
        ) {
            // 1. Ambil dan kunci stok gudang asal
            $sourceStock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $fromWarehouseId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock) {
                throw new RuntimeException(
                    'Stok produk di gudang asal belum tersedia.'
                );
            }

            if ($sourceStock->quantity < $quantity) {
                throw new RuntimeException(
                    'Stok gudang asal tidak mencukupi.'
                );
            }

            // 2. Siapkan stok gudang tujuan
            Stock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'warehouse_id' => $toWarehouseId,
                ],
                [
                    'quantity' => 0,
                ]
            );

            // Kunci stok gudang tujuan
            $destinationStock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $toWarehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Simpan kondisi stok sebelum transfer
            $sourceBefore = $sourceStock->quantity;
            $destinationBefore = $destinationStock->quantity;

            // 4. Update stok
            $sourceStock->quantity -= $quantity;
            $sourceStock->save();

            $destinationStock->quantity += $quantity;
            $destinationStock->save();

            // 5. Catat movement keluar
            StockMovement::create([
                'stock_id' => $sourceStock->id,
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouseId,
                'type' => 'TRANSFER_OUT',
                'quantity' => $quantity,
                'stock_before' => $sourceBefore,
                'stock_after' => $sourceStock->quantity,
                'reference_type' => 'TRANSFER',
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            // 6. Catat movement masuk
            StockMovement::create([
                'stock_id' => $destinationStock->id,
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'type' => 'TRANSFER_IN',
                'quantity' => $quantity,
                'stock_before' => $destinationBefore,
                'stock_after' => $destinationStock->quantity,
                'reference_type' => 'TRANSFER',
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);
        });
    }
}
