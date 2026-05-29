<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'seller_email')) {
                $table->string('seller_email')->nullable()->after('mandate_type');
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'external_message_id')) {
                $table->string('external_message_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'seller_email')) {
                $table->dropColumn('seller_email');
            }
        });
    }
};
