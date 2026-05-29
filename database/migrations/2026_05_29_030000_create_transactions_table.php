<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attorney_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->enum('status', ['initiated', 'fica_pending', 'fica_verified', 'offer_accepted', 'conveyancing', 'registration', 'completed', 'cancelled'])->default('initiated');
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->decimal('agent_split', 5, 2)->default(50.00);
            $table->date('offer_date')->nullable();
            $table->date('deadline')->nullable();
            $table->date('estimated_close_date')->nullable();
            $table->date('closed_at')->nullable();
            $table->json('timeline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['deal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
