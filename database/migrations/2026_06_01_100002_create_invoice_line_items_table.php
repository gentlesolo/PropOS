<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->enum('category', ['rent', 'deposit', 'utility', 'parking', 'maintenance', 'late_fee', 'other'])->default('rent');
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->boolean('is_taxable')->default(false);
            $table->timestamps();

            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
