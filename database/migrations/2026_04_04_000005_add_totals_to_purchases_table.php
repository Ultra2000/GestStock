<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'total_ht')) {
                $table->decimal('total_ht', 15, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('purchases', 'total_vat')) {
                $table->decimal('total_vat', 15, 2)->default(0)->after('total_ht');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('purchases', 'total_ht')) {
                $columns[] = 'total_ht';
            }
            if (Schema::hasColumn('purchases', 'total_vat')) {
                $columns[] = 'total_vat';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
