<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email_address');
            $table->boolean('is_shared')->default(false);

            // IMAP (receiving)
            $table->string('imap_host')->nullable();
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->enum('imap_encryption', ['ssl', 'tls', 'none'])->default('ssl');

            // SMTP (outbound override)
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->default('tls');

            // Credentials (stored encrypted)
            $table->text('username')->nullable();
            $table->text('password')->nullable();

            $table->string('email_signature_html', 2000)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
