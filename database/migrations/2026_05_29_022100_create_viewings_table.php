<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viewings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('assigned_agent_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('scheduled'); // scheduled, confirmed, completed, no_show, cancelled
            $table->integer('duration_minutes')->default(30);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['agency_id', 'assigned_agent_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewings');
    }
};
