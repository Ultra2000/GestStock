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
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('unit_price_ht', 10, 2)->default(0);
                $table->decimal('vat_rate', 5, 2)->default(20);
                $table->string('vat_category', 5)->default('S');
                $table->decimal('vat_amount', 10, 2)->default(0);
                $table->decimal('total_price_ht', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
