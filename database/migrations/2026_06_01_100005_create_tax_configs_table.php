<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->enum('tax_type', ['vat', 'withholding', 'municipal', 'other'])->default('vat');
            $table->decimal('rate', 5, 2)->default(15.00);
            $table->enum('applies_to', ['all', 'residential', 'commercial'])->default('all');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['agency_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_configs');
    }
};
