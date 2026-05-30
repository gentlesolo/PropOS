<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_number');
            $table->string('from_number')->nullable();
            $table->text('body');
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'undelivered'])->default('queued');
            $table->string('provider')->default('twilio');
            $table->string('provider_message_id')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'direction']);
            $table->index(['contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
