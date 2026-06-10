<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'bi_yearly', 'yearly'])
                ->default('monthly')
                ->after('payment_day');

            $table->decimal('agency_fee', 15, 2)->nullable()->after('deposit_amount');
            $table->decimal('legal_fee', 15, 2)->nullable()->after('agency_fee');
            $table->decimal('service_charge', 15, 2)->nullable()->after('legal_fee');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['payment_frequency', 'agency_fee', 'legal_fee', 'service_charge']);
        });
    }
};
