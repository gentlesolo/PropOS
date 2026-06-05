<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('thread_id')->nullable()->after('agency_id')->constrained('email_threads')->nullOnDelete();
            $table->foreignId('email_account_id')->nullable()->after('thread_id')->constrained('email_accounts')->nullOnDelete();
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound')->after('email_account_id');
            $table->string('from_email')->nullable()->after('direction');
            $table->string('from_name')->nullable()->after('from_email');
            $table->longText('body_html')->nullable()->after('from_name');
            $table->longText('body_text')->nullable()->after('body_html');
            $table->string('message_id', 512)->nullable()->after('body_text');
            $table->string('in_reply_to', 512)->nullable()->after('message_id');
            $table->json('attachments')->nullable()->after('in_reply_to');
            $table->timestamp('read_at')->nullable()->after('attachments');

            $table->index(['agency_id', 'direction']);
            $table->index(['thread_id']);
            $table->index(['email_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('thread_id');
            $table->dropConstrainedForeignId('email_account_id');
            $table->dropColumn([
                'direction', 'from_email', 'from_name',
                'body_html', 'body_text', 'message_id',
                'in_reply_to', 'attachments', 'read_at',
            ]);
        });
    }
};
