<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_graphics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('format');           // square | landscape | story
            $table->string('channel');          // instagram | facebook | linkedin | whatsapp | general
            $table->string('file_path');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('post_copy')->nullable(); // {caption, hashtags, char_count}
            $table->timestamps();

            $table->index(['listing_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_graphics');
    }
};
