<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('sale_price', 15, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('gross_commission', 15, 2);
            $table->decimal('agent_split_percentage', 5, 2);
            $table->decimal('agent_commission', 15, 2);
            $table->decimal('agency_commission', 15, 2);
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'disputed'])->default('pending');
            $table->date('expected_payment_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'payment_status']);
            $table->index(['agent_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
