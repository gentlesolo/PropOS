<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LiveKit room name as the primary call identifier (replaces Twilio CallSid for routing)
        Schema::table('calls', function (Blueprint $table) {
            $table->string('livekit_room_name', 80)->nullable()->after('provider_call_sid');
            $table->index('livekit_room_name');
        });

        // OTP fields for BYON verification (replaces Twilio OutgoingCallerId flow)
        Schema::table('agent_numbers', function (Blueprint $table) {
            $table->string('verification_code', 10)->nullable()->after('caller_id_sid');
            $table->timestamp('verification_expires_at')->nullable()->after('verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['livekit_room_name']);
            $table->dropColumn('livekit_room_name');
        });

        Schema::table('agent_numbers', function (Blueprint $table) {
            $table->dropColumn(['verification_code', 'verification_expires_at']);
        });
    }
};
