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
        Schema::create('call_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->text('summary_text');
            $table->json('key_points')->nullable();
            $table->enum('sentiment', ['hot', 'warm', 'cold', 'neutral'])->default('neutral');
            $table->tinyInteger('sentiment_score')->default(50);
            $table->json('action_items')->nullable();
            $table->text('suggested_next_step')->nullable();
            $table->string('gpt_model', 30)->default('gpt-4o');
            $table->timestamp('agent_confirmed_at')->nullable();
            $table->boolean('agent_edited')->default(false);
            $table->timestamps();

            $table->index('call_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_summaries');
    }
};
