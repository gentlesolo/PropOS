<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->boolean('reminder_30d_sent')->default(false)->after('renewed_until');
            $table->boolean('reminder_14d_sent')->default(false)->after('reminder_30d_sent');
            $table->boolean('reminder_7d_sent')->default(false)->after('reminder_14d_sent');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['reminder_30d_sent', 'reminder_14d_sent', 'reminder_7d_sent']);
        });
    }
};
