<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->integer('current_step')->default(0);
            $table->timestamp('next_action_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status', 'next_action_at']);
            $table->index(['contact_id', 'status']);
        });

        Schema::create('follow_up_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('follow_up_sequences')->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->enum('type', ['email', 'call', 'sms', 'task'])->default('email');
            $table->string('subject')->nullable();
            $table->text('message_template');
            $table->unsignedInteger('delay_days')->default(1);
            $table->enum('status', ['pending', 'sent', 'skipped'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['sequence_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_steps');
        Schema::dropIfExists('follow_up_sequences');
    }
};
