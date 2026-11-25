@extends('layouts.app')

@section('title', 'Inventory | StockPilot')
@section('page-title', 'Inventory')

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory</h4>
        <p class="text-muted mb-0">
            Monitor persediaan produk di setiap gudang.
        </p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="content-card">
            <p class="text-muted mb-2">Produk Aktif</p>
            <h3 class="fw-bold mb-0">
                {{ number_format($totalProducts) }}
            </h3>
        </div>
    </div>

    <div class="col-md-6">
        <div class="content-card">
            <p class="text-muted mb-2">Total Stok</p>
            <h3 class="fw-bold mb-0">
                {{ number_format($totalStock) }}
                <small class="text-muted fs-6">unit</small>
            </h3>
        </div>
    </div>
</div>


{{-- Stock In & Stock Out Forms --}}
<div class="row g-3 mb-4">

    {{-- Stock In --}}
    <div class="col-lg-6">
        <div class="content-card h-100">
            <h5 class="fw-semibold mb-3">Stock In</h5>
            <p class="text-muted small mb-4">
                Tambahkan persediaan produk ke gudang.
            </p>

            <form method="POST" action="{{ route('inventory.stock-in') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->sku }} - {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gudang</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">Pilih gudang</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        min="1"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea
                        name="notes"
                        class="form-control"
                        rows="2"
                        placeholder="Contoh: Pembelian barang"
                    ></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Tambah Stok
                </button>
            </form>
        </div>
    </div>

    {{-- Stock Out --}}
    <div class="col-lg-6">
        <div class="content-card h-100">
            <h5 class="fw-semibold mb-3">Stock Out</h5>
            <p class="text-muted small mb-4">
                Keluarkan persediaan produk dari gudang.
            </p>

            <form method="POST" action="{{ route('inventory.stock-out') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->sku }} - {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gudang</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">Pilih gudang</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        min="1"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea
                        name="notes"
                        class="form-control"
                        rows="2"
                        placeholder="Contoh: Barang rusak"
                    ></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Keluarkan Stok
                </button>
            </form>
        </div>
    </div>

</div>

{{-- Inventory Table --}}
<div class="content-card">

    <form method="GET" action="{{ route('inventory.index') }}"
          class="row g-3 mb-4">

        <div class="col-md-5">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Cari nama produk atau SKU..."
                value="{{ request('search') }}"
            >
        </div>

        <div class="col-md-4">
            <select name="warehouse_id" class="form-select">
                <option value="">Semua Gudang</option>

                @foreach ($warehouses as $warehouse)
                    <option
                        value="{{ $warehouse->id }}"
                        {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}
                    >
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                Cari
            </button>

            <a href="{{ route('inventory.index') }}"
               class="btn btn-outline-secondary">
                Reset
            </a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Gudang</th>
                    <th class="text-end">Stok</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($stocks as $stock)
                    <tr>
                        <td>
                            {{ $stocks->firstItem() + $loop->index }}
                        </td>

                        <td>
                            <span class="fw-semibold">
                                {{ $stock->product->sku }}
                            </span>
                        </td>

                        <td>
                            {{ $stock->product->name }}
                        </td>

                        <td>
                            {{ $stock->product->category->name ?? '-' }}
                        </td>

                        <td>
                            {{ $stock->warehouse->name }}
                        </td>

                        <td class="text-end">
                            <span class="badge"
                            style="background-color: #3b2b12; color: #fbbf24;">
                                {{ number_format($stock->quantity) }}
                                {{ $stock->product->unit }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Data inventory tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $stocks->links() }}
    </div>

</div>

@endsection
