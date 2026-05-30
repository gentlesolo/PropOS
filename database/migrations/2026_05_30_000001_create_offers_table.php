<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'countered', 'accepted', 'rejected', 'expired', 'withdrawn'])->default('pending');
            $table->enum('type', ['sale', 'rental'])->default('sale');
            $table->date('expiry_date')->nullable();
            $table->date('proposed_occupation_date')->nullable();
            $table->decimal('deposit_amount', 15, 2)->nullable();
            $table->text('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('counter_amount', 15, 2)->nullable();
            $table->text('counter_notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['deal_id']);
            $table->index(['listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
