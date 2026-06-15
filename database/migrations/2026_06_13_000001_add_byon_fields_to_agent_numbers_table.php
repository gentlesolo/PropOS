<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_numbers', function (Blueprint $table) {
            $table->string('twilio_number', 20)->nullable()->change();

            if (! Schema::hasColumn('agent_numbers', 'display_number')) {
                $table->string('display_number', 20)->nullable();
            }
            if (! Schema::hasColumn('agent_numbers', 'number_type')) {
                $table->string('number_type', 30)->default('twilio_provisioned');
            }
            if (! Schema::hasColumn('agent_numbers', 'country_code')) {
                $table->string('country_code', 2)->default('US');
            }
            if (! Schema::hasColumn('agent_numbers', 'verified')) {
                $table->boolean('verified')->default(false);
            }
            if (! Schema::hasColumn('agent_numbers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (! Schema::hasColumn('agent_numbers', 'caller_id_sid')) {
                $table->string('caller_id_sid', 50)->nullable();
            }
        });

        // Add unique constraint only if it doesn't exist yet
        try {
            Schema::table('agent_numbers', function (Blueprint $table) {
                $table->unique(['agency_id', 'display_number']);
            });
        } catch (\Exception) {
            // Constraint already exists — safe to ignore
        }

        // Backfill display_number = twilio_number for all existing rows
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE agent_numbers SET display_number = twilio_number, verified = 1, verified_at = CURRENT_TIMESTAMP WHERE display_number IS NULL AND twilio_number IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('agent_numbers', function (Blueprint $table) {
            $table->dropUnique(['agency_id', 'display_number']);
            $table->dropColumn([
                'display_number',
                'number_type',
                'country_code',
                'verified',
                'verified_at',
                'caller_id_sid',
            ]);
            $table->string('twilio_number', 20)->nullable(false)->change();
        });
    }
};
