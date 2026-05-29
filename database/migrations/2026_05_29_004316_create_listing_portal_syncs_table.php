<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_portal_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('portal_id')->constrained('portals')->cascadeOnDelete();
            $table->enum('status', ['pending', 'syncing', 'synced', 'failed', 'unpublished'])->default('pending');
            $table->string('external_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('sync_errors')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['listing_id', 'portal_id']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_portal_syncs');
    }
};
