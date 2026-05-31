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
        Schema::table('agent_numbers', function (Blueprint $table) {
            // IETF language tag for transcription (en, fr, yo, ig, ha, pt, ar…)
            $table->string('language', 10)->default('en')->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('agent_numbers', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
