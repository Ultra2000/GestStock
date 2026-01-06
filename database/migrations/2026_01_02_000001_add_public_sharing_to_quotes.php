<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('public_token')->unique()->nullable()->after('quote_number');
            $table->text('refusal_reason')->nullable()->after('rejected_at');
            $table->timestamp('expires_at')->nullable()->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'refusal_reason', 'expires_at']);
        });
    }
};
