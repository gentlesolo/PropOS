<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->timestamp('deposit_refunded_at')->nullable()->after('reminder_7d_sent');
            $table->json('deposit_deductions')->nullable()->after('deposit_refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['deposit_refunded_at', 'deposit_deductions']);
        });
    }
};
