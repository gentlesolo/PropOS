<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viewing_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewing_id')->constrained('viewings')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->unsignedTinyInteger('price_perception')->nullable();
            $table->string('interest_level')->nullable();
            $table->text('positive_notes')->nullable();
            $table->text('concerns')->nullable();
            $table->boolean('would_make_offer')->default(false);
            $table->text('agent_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewing_feedbacks');
    }
};
