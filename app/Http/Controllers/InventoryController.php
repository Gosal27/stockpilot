<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use RuntimeException;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('is_active', true)
        ->orderBy('name')
        ->get();

        $stocks = Stock::with([
            'product.category',
            'warehouse',
        ])
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('product_id')
            ->paginate(15)
            ->withQueryString();

        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalProducts = Product::where('is_active', true)->count();

        $totalStock = Stock::sum('quantity');

        return view('inventory.index', compact(
            'stocks',
            'warehouses',
            'totalProducts',
            'totalStock',
            'products'
        ));
    }
    public function stockIn(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $inventoryService->stockIn(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (int) $validated['quantity'],
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('inventory.index')
                ->with('success', 'Stok berhasil ditambahkan.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
    public function stockOut(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $inventoryService->stockOut(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (int) $validated['quantity'],
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('inventory.index')
                ->with('success', 'Stok berhasil dikeluarkan.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
