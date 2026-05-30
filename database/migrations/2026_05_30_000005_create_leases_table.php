<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('reference')->unique();
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'renewed', 'terminated'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->nullable();
            $table->integer('escalation_percent')->default(0);
            $table->enum('payment_day', ['1', '2', '3', '5', '7', '10', '15', '25', '28', '30'])->default('1');
            $table->string('bank_account')->nullable();
            $table->text('special_conditions')->nullable();
            $table->date('renewed_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['tenant_id']);
            $table->index(['listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
