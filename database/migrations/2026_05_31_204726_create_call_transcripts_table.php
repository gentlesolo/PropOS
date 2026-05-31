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
        Schema::create('call_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->longText('full_text');
            $table->json('speaker_segments')->nullable();
            $table->unsignedSmallInteger('word_count')->default(0);
            $table->string('language', 10)->default('en');
            $table->string('whisper_model', 30)->default('whisper-1');
            $table->unsignedSmallInteger('processing_seconds')->nullable();
            $table->timestamps();

            $table->index('call_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_transcripts');
    }
};
