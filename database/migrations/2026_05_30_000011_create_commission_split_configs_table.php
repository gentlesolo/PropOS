<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_split_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->enum('applies_to', ['agency_default', 'role', 'agent'])->default('agency_default');
            $table->string('role')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->decimal('agent_split', 5, 2)->default(50.00);
            $table->decimal('agency_split', 5, 2)->default(50.00);
            $table->decimal('referral_split', 5, 2)->default(0.00);
            $table->decimal('franchise_fee', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['agency_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_split_configs');
    }
};
