<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paystack_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('type');                          // subscription | topup | charge
            $table->string('event')->nullable();             // webhook event that triggered this record
            $table->string('status');                        // success | failed | abandoned
            $table->unsignedBigInteger('amount');            // in kobo
            $table->string('currency', 3)->default('NGN');
            $table->string('plan')->nullable();              // pricing plan key e.g. solo, agency_pro
            $table->string('billing_cycle')->nullable();     // monthly | annual
            $table->integer('credits_added')->nullable();    // for topup transactions
            $table->string('paystack_transaction_id')->nullable();
            $table->string('paystack_customer_code')->nullable();
            $table->string('paystack_subscription_code')->nullable();
            $table->string('authorization_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'type']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paystack_transactions');
    }
};
