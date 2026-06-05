<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->json('participants')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'is_archived', 'last_message_at']);
            $table->index(['contact_id']);
            $table->index(['assigned_to']);
            $table->index(['email_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};
