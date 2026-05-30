<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained('leases')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspector_name')->nullable();
            $table->string('inspector_company')->nullable();
            $table->enum('type', ['pre_purchase', 'pre_rental', 'routine', 'exit', 'appraisal', 'building', 'pest', 'electrical', 'plumbing'])->default('pre_purchase');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('result', ['pass', 'pass_with_conditions', 'fail', 'pending'])->default('pending');
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('summary')->nullable();
            $table->json('findings')->nullable();
            $table->string('report_path')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['listing_id']);
            $table->index(['deal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
