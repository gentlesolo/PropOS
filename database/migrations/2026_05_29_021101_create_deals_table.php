<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['sale', 'rental'])->default('sale');
            $table->decimal('value', 15, 2)->default(0);
            $table->integer('momentum_score')->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['agency_id', 'pipeline_stage_id']);
            $table->index(['agency_id', 'assigned_agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
