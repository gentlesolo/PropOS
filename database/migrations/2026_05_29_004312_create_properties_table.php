<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province');
            $table->string('country', 2);
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('property_type', ['house', 'apartment', 'townhouse', 'penthouse', 'land', 'commercial', 'office', 'warehouse']);
            $table->string('property_subtype')->nullable();
            $table->tinyInteger('bedrooms')->nullable();
            $table->tinyInteger('bathrooms')->nullable();
            $table->tinyInteger('parking_spaces')->nullable();
            $table->decimal('floor_area_sqm', 10, 2)->nullable();
            $table->decimal('land_area_sqm', 10, 2)->nullable();
            $table->smallInteger('year_built')->nullable();
            $table->enum('condition', ['new', 'excellent', 'good', 'fair', 'needs_work'])->nullable();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
