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
        Schema::table('viewings', function (Blueprint $table) {
            $table->timestamp('check_in_at')->nullable()->after('duration_minutes');
            $table->text('outcome_notes')->nullable()->after('check_in_at');
            $table->enum('outcome', ['interested', 'not_interested', 'offer_expected', 'undecided'])
                  ->nullable()->after('outcome_notes');
        });
    }

    public function down(): void
    {
        Schema::table('viewings', function (Blueprint $table) {
            $table->dropColumn(['check_in_at', 'outcome_notes', 'outcome']);
        });
    }
};
