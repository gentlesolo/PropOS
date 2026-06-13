<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_numbers', function (Blueprint $table) {
            // Make twilio_number nullable — BYON numbers have no Twilio-provisioned number
            $table->string('twilio_number', 20)->nullable()->change();

            // The number shown to leads as caller ID (agency's real number for BYON,
            // same as twilio_number for platform-provisioned numbers)
            $table->string('display_number', 20)->nullable()->after('twilio_number');

            // How the number was registered
            $table->enum('number_type', ['twilio_provisioned', 'verified_caller_id'])
                ->default('twilio_provisioned')
                ->after('display_number');

            // ISO 3166-1 alpha-2 country code (NG, ZA, GB, US …)
            $table->string('country_code', 2)->default('US')->after('number_type');

            // Verification state — Twilio-provisioned numbers start as verified
            $table->boolean('verified')->default(false)->after('country_code');
            $table->timestamp('verified_at')->nullable()->after('verified');

            // Twilio OutgoingCallerId SID stored after verification completes (BYON only)
            $table->string('caller_id_sid', 50)->nullable()->after('verified_at');

            // Unique display_number per agency — prevents duplicate registrations
            $table->unique(['agency_id', 'display_number']);
        });

        // Backfill display_number = twilio_number for all existing rows
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE agent_numbers SET display_number = twilio_number, verified = 1, verified_at = NOW() WHERE display_number IS NULL AND twilio_number IS NOT NULL'
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
