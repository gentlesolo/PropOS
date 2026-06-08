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
        Schema::table('agencies', function (Blueprint $table) {
            $table->integer('ai_credits_balance')->default(200)->after('subscription_status');
            $table->integer('ai_credits_allocated_monthly')->default(200)->after('ai_credits_balance');
            $table->string('billing_cycle')->default('monthly')->after('ai_credits_allocated_monthly');
            $table->string('paystack_customer_code')->nullable()->after('billing_cycle');
            $table->string('paystack_subscription_code')->nullable()->after('paystack_customer_code');
        });

        // Update existing default
        \Illuminate\Support\Facades\DB::table('agencies')
            ->where('subscription_plan', 'starter')
            ->update(['subscription_plan' => 'solo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'ai_credits_balance',
                'ai_credits_allocated_monthly',
                'billing_cycle',
                'paystack_customer_code',
                'paystack_subscription_code',
            ]);
        });
    }
};
