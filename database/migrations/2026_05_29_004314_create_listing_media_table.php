<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->enum('file_type', ['image', 'video', 'document', 'floor_plan'])->default('image');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'order']);
            $table->index(['listing_id', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_media');
    }
};
