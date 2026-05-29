<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['buyer', 'seller', 'landlord', 'tenant', 'investor', 'referral_partner'])->default('buyer');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('source')->nullable();
            $table->string('source_detail')->nullable();
            $table->tinyInteger('intent_score')->default(0);
            $table->enum('status', ['new', 'active', 'qualified', 'nurturing', 'closed', 'archived'])->default('new');
            $table->json('preferences')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['agency_id', 'type']);
            $table->index(['agency_id', 'assigned_agent_id']);
            $table->index(['agency_id', 'status']);
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
