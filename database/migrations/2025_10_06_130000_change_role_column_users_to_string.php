<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Transformer la colonne enum role en string pour accepter 'cashier'
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_tmp')->default('user');
        });

        DB::statement('UPDATE users SET role_tmp = role');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_tmp', 'role');
        });

        // Optionnel: index si filtrage fréquent
        // Schema::table('users', function (Blueprint $table) { $table->index('role'); });
    }

    public function down(): void
    {
        // Recréation de l'enum d'origine (admin, manager, user) – les caissiers deviendront 'user'
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role_enum_old', ['admin','manager','user'])->default('user');
        });
        DB::statement("UPDATE users SET role_enum_old = CASE WHEN role IN ('admin','manager','user') THEN role ELSE 'user' END");
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('role'); });
        Schema::table('users', function (Blueprint $table) { $table->renameColumn('role_enum_old','role'); });
    }
};