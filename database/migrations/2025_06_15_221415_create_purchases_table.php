<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number');
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->decimal('total', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
}; 