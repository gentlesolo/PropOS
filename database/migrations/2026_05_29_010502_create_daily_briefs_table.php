<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->json('priority_actions')->nullable();
            $table->json('deal_alerts')->nullable();
            $table->json('viewing_schedule')->nullable();
            $table->text('market_snapshot')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
            $table->index(['agency_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_briefs');
    }
};
