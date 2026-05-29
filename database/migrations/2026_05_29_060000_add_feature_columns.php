<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Viewings: feedback survey tracking + feedback token
        Schema::table('viewings', function (Blueprint $table) {
            if (! Schema::hasColumn('viewings', 'feedback_survey_sent')) {
                $table->boolean('feedback_survey_sent')->default(false)->after('booking_source');
            }
            if (! Schema::hasColumn('viewings', 'feedback_token')) {
                $table->string('feedback_token')->nullable()->after('feedback_survey_sent');
            }
        });

        // Agency: commission split configuration
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'commission_splits')) {
                $table->json('commission_splits')->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('agencies', 'default_commission_rate')) {
                $table->decimal('default_commission_rate', 5, 2)->default(5.00)->after('commission_splits');
            }
        });

        // Training progress: ensure score column exists
        Schema::table('training_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('training_progress', 'score')) {
                $table->unsignedTinyInteger('score')->nullable()->after('status');
            }
            if (! Schema::hasColumn('training_progress', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('score');
            }
            if (! Schema::hasColumn('training_progress', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('viewings', function (Blueprint $table) {
            $table->dropColumn(['feedback_survey_sent', 'feedback_token']);
        });
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['commission_splits', 'default_commission_rate']);
        });
    }
};
