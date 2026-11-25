<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');});

Route::get('/inventory', [InventoryController::class, 'index'])
    ->name('inventory.index');

Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn'])
    ->name('inventory.stock-in');

Route::post('/inventory/stock-out', [InventoryController::class, 'stockOut'])
    ->name('inventory.stock-out');
