<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('subscription_status')->default('trial')->after('is_active');
            $table->string('subscription_plan')->default('trial')->after('subscription_status');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'subscription_plan', 'trial_ends_at', 'subscription_ends_at']);
        });
    }
};
