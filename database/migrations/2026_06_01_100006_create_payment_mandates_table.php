<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained('leases')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('gateway');
            $table->string('gateway_mandate_id')->nullable();
            $table->enum('status', ['pending', 'active', 'cancelled', 'failed'])->default('pending');
            $table->unsignedTinyInteger('collection_day');
            $table->decimal('amount', 15, 2);
            $table->timestamp('last_collected_at')->nullable();
            $table->date('next_collection_date')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['lease_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_mandates');
    }
};
