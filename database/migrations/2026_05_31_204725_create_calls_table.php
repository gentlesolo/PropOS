<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('status', [
                'initiated', 'ringing', 'in-progress', 'completed',
                'no-answer', 'busy', 'failed', 'canceled',
            ])->default('initiated');
            $table->string('provider_call_sid', 50)->nullable()->unique();
            $table->string('twilio_number', 20)->nullable();
            $table->string('remote_number', 20)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->string('recording_url')->nullable();
            $table->string('recording_sid', 50)->nullable();
            $table->boolean('consent_played')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'agent_id']);
            $table->index(['agency_id', 'contact_id']);
            $table->index(['agency_id', 'status']);
            $table->index('provider_call_sid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
