<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_briefs', function (Blueprint $table) {
            $table->json('coaching_tips')->nullable()->after('market_snapshot');
            $table->json('goals')->nullable()->after('coaching_tips');
            $table->text('ai_summary')->nullable()->after('goals');
            $table->integer('focus_score')->nullable()->after('ai_summary');
        });
    }

    public function down(): void
    {
        Schema::table('daily_briefs', function (Blueprint $table) {
            $table->dropColumn(['coaching_tips', 'goals', 'ai_summary', 'focus_score']);
        });
    }
};
