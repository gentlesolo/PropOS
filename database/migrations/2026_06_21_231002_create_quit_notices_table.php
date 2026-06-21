<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quit_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users');
            $table->string('reference')->unique();
            $table->date('issue_date');
            $table->date('vacate_by_date');
            $table->string('reason');
            $table->text('notice_body');
            $table->text('ai_draft')->nullable();
            $table->enum('status', ['drafted', 'sent', 'acknowledged', 'disputed', 'withdrawn', 'completed'])->default('drafted');
            $table->string('delivery_method')->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('tenant_response')->nullable();
            $table->text('internal_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quit_notices');
    }
};
