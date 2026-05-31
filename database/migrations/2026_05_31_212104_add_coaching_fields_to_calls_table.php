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
        Schema::table('calls', function (Blueprint $table) {
            $table->boolean('flagged_for_coaching')->default(false)->after('consent_played');
            $table->text('coaching_notes')->nullable()->after('flagged_for_coaching');
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete()->after('coaching_notes');
            $table->string('live_transcript_channel', 100)->nullable()->after('flagged_by');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['flagged_by']);
            $table->dropColumn(['flagged_for_coaching', 'coaching_notes', 'flagged_by', 'live_transcript_channel']);
        });
    }
};
