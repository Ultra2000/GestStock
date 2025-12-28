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
        // SQLite doesn't support modifying enum columns directly or easily.
        // We need to recreate the table or just remove the check constraint if possible, 
        // but Laravel/Doctrine DBAL has limitations with SQLite enums.
        // The best approach for SQLite in development is often to just change the column definition to string
        // or recreate the table. Since we are in dev, let's try to modify the column to string which accepts any value.
        
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can't easily revert to the specific enum list without raw SQL or recreating table
    }
};
