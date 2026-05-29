<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('scheduled'); // scheduled, live, completed, cancelled
            $table->unsignedInteger('rsvp_count')->default(0);
            $table->unsignedInteger('attendance_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('rsvp_slug')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'starts_at']);
        });

        Schema::create('open_house_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->dateTime('checked_in_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_house_rsvps');
        Schema::dropIfExists('open_houses');
    }
};
