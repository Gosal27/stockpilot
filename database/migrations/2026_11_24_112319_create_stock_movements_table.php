<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_id')
                ->constrained('stocks')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->string('type', 30);

            $table->unsignedBigInteger('quantity');

            $table->unsignedBigInteger('stock_before');
            $table->unsignedBigInteger('stock_after');

            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['product_id', 'warehouse_id', 'created_at'],
                'stock_movements_product_warehouse_date_index'
            );

            $table->index(
                ['reference_type', 'reference_id'],
                'stock_movements_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
