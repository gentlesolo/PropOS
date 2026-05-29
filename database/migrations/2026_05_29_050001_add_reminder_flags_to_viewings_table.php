<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viewings', function (Blueprint $table) {
            $table->boolean('reminder_48h_sent')->default(false)->after('status');
            $table->boolean('reminder_morning_sent')->default(false)->after('reminder_48h_sent');
            $table->boolean('reminder_1h_sent')->default(false)->after('reminder_morning_sent');
            $table->string('booking_source')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('viewings', function (Blueprint $table) {
            $table->dropColumn(['reminder_48h_sent', 'reminder_morning_sent', 'reminder_1h_sent', 'booking_source']);
        });
    }
};
