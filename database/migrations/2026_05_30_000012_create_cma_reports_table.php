<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cma_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('subject_address');
            $table->decimal('estimated_value_low', 15, 2)->nullable();
            $table->decimal('estimated_value_high', 15, 2)->nullable();
            $table->decimal('recommended_list_price', 15, 2)->nullable();
            $table->json('comparable_sales')->nullable();
            $table->json('market_stats')->nullable();
            $table->text('summary')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['agency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cma_reports');
    }
};
