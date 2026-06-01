<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('name');
            $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
            $table->json('monthly_income_targets');
            $table->json('monthly_expense_targets');
            $table->decimal('vacancy_rate_assumption', 5, 2)->default(5.00);
            $table->decimal('escalation_assumption', 5, 2)->default(7.00);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
