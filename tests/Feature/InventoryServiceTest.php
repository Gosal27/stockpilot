<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_stock_out_fails_when_stock_is_insufficient(): void
    {
        $category = Category::create([
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SP-003',
            'name' => 'USB Cable',
            'unit' => 'pcs',
            'cost_price' => 20000,
            'selling_price' => 35000,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-003',
            'name' => 'Gudang Testing',
            'is_active' => true,
        ]);

        $service = app(InventoryService::class);

        // Masukkan stok awal sebanyak 5 unit.
        $service->stockIn(
            $product->id,
            $warehouse->id,
            5,
            'Stok awal testing'
        );

        // Coba keluarkan 8 unit, padahal stok hanya 5.
        try {
            $service->stockOut(
                $product->id,
                $warehouse->id,
                8,
                'Testing stok tidak cukup'
            );

            $this->fail('Seharusnya stok keluar ditolak.');
        } catch (\RuntimeException $exception) {
            $this->assertEquals(
                'Stok tidak mencukupi.',
                $exception->getMessage()
            );
        }

        // Stok harus tetap 5.
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        // Movement hanya 1, yaitu stok masuk.
        $this->assertDatabaseCount('stock_movements', 1);
    }
    public function test_stock_in_increases_stock_and_creates_movement(): void
    {
        $category = Category::create([
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ACC-001',
            'name' => 'Mouse',
            'unit' => 'pcs',
            'cost_price' => 50000,
            'selling_price' => 75000,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);

        $service = app(InventoryService::class);

        $service->stockIn(
            $product->id,
            $warehouse->id,
            10,
            'Test stock in'
        );

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'IN',
            'quantity' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
        ]);
    }
    public function test_stock_out_decreases_stock_and_creates_movement(): void
    {
        $category = Category::create([
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ACC-002',
            'name' => 'Keyboard',
            'unit' => 'pcs',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
        ]);

        $service = app(InventoryService::class);

        $service->stockOut(
            $product->id,
            $warehouse->id,
            5,
            'Test stock out'
        );

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'OUT',
            'quantity' => 5,
            'stock_before' => 20,
            'stock_after' => 15,
        ]);
    }
    public function test_stock_transfer_moves_stock_between_warehouses(): void
    {
        $category = Category::create([
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ACC-003',
            'name' => 'Headset',
            'unit' => 'pcs',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'is_active' => true,
        ]);

        $warehouseA = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang A',
            'is_active' => true,
        ]);

        $warehouseB = Warehouse::create([
            'code' => 'WH-02',
            'name' => 'Gudang B',
            'is_active' => true,
        ]);

        $stockA = Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 20,
        ]);

        $service = app(InventoryService::class);

        $service->transfer(
            $product->id,
            $warehouseA->id,
            $warehouseB->id,
            5,
            'Transfer antar gudang'
        );

        // Stok gudang asal berkurang
        $this->assertDatabaseHas('stocks', [
            'id' => $stockA->id,
            'quantity' => 15,
        ]);

        // Stok gudang tujuan bertambah
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 5,
        ]);

        // Total stok tetap 20
        $totalStock = Stock::where('product_id', $product->id)
            ->sum('quantity');

        $this->assertEquals(20, $totalStock);

        // Transfer menghasilkan dua movement
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouseA->id,
            'type' => 'TRANSFER_OUT',
            'quantity' => 5,
            'stock_before' => 20,
            'stock_after' => 15,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouseB->id,
            'type' => 'TRANSFER_IN',
            'quantity' => 5,
            'stock_before' => 0,
            'stock_after' => 5,
        ]);

        $this->assertDatabaseCount('stock_movements', 2);
    }
    public function test_stock_transfer_fails_when_source_stock_is_insufficient(): void
    {
        $category = Category::create([
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ACC-004',
            'name' => 'Webcam',
            'unit' => 'pcs',
            'cost_price' => 200000,
            'selling_price' => 300000,
            'is_active' => true,
        ]);

        $warehouseA = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang A',
            'is_active' => true,
        ]);

        $warehouseB = Warehouse::create([
            'code' => 'WH-02',
            'name' => 'Gudang B',
            'is_active' => true,
        ]);

        $stockA = Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 3,
        ]);

        $service = app(InventoryService::class);

        try {
            $service->transfer(
                $product->id,
                $warehouseA->id,
                $warehouseB->id,
                5,
                'Transfer stok tidak mencukupi'
            );

            $this->fail('Transfer seharusnya ditolak.');
        } catch (\RuntimeException $exception) {
            $this->assertEquals(
                'Stok gudang asal tidak mencukupi.',
                $exception->getMessage()
            );
        }

        // Stok asal tetap 3
        $this->assertDatabaseHas('stocks', [
            'id' => $stockA->id,
            'quantity' => 3,
        ]);

        // Stok tujuan tidak boleh terbentuk karena transaksi rollback
        $this->assertDatabaseMissing('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouseB->id,
        ]);

        // Tidak ada movement yang tercatat
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_transfer_fails_when_source_and_destination_are_same(): void
    {
        $service = app(InventoryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Gudang asal dan tujuan tidak boleh sama.'
        );

        $service->transfer(
            1,
            1,
            1,
            5
        );
    }

    public function test_transfer_fails_when_quantity_is_zero(): void
    {
        $service = app(InventoryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Jumlah transfer harus lebih dari 0.'
        );

        $service->transfer(
            1,
            1,
            2,
            0
        );
    }
}
