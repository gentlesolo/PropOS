<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->enum('mandate_type', ['sole', 'open', 'rental']);
            $table->enum('status', ['draft', 'active', 'under_offer', 'sold', 'let', 'withdrawn', 'expired'])->default('draft');
            $table->decimal('listing_price', 15, 2);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->date('mandate_start_date');
            $table->date('mandate_end_date')->nullable();
            $table->text('description_short')->nullable();
            $table->text('description_standard')->nullable();
            $table->text('description_long')->nullable();
            $table->string('headline')->nullable();
            $table->json('features_highlighted')->nullable();
            $table->string('listing_url')->nullable();
            $table->integer('days_on_market')->nullable();
            $table->tinyInteger('health_score')->nullable();
            $table->json('portal_ids')->nullable();
            $table->enum('seller_report_frequency', ['weekly', 'biweekly', 'monthly'])->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['agency_id', 'status', 'listing_price']);
            $table->index(['agency_id', 'agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
