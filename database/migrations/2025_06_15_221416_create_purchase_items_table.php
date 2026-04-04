<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('unit_price_ht', 10, 2)->default(0);
                $table->decimal('vat_rate', 5, 2)->default(20);
                $table->decimal('vat_amount', 10, 2)->default(0);
                $table->decimal('total_price_ht', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
}; 