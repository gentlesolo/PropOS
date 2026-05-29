<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['portal', 'referral', 'walk_in', 'social_media', 'direct', 'campaign', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['agency_id', 'is_active']);
            $table->unique(['agency_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
