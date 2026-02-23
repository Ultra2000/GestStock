<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Changer le type de quantity de integer à decimal(10,2) pour
     * permettre les quantités décimales (ex: 1.5 kg, 0.75 m, etc.)
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite ne supporte pas ALTER COLUMN, mais accepte les décimaux
            // dans les colonnes INTEGER sans modification de schéma.
            // Les nouvelles valeurs seront simplement stockées comme REAL.
            // Pas de modification nécessaire pour SQLite.
        } else {
            // MySQL / MariaDB / PostgreSQL
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 2)->change();
            });

            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 2)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->integer('quantity')->change();
            });

            Schema::table('purchase_items', function (Blueprint $table) {
                $table->integer('quantity')->change();
            });
        }
    }
};
