<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('reminder_type', [
                'inspection',
                'certification',
                'fica',
                'audit',
                'lease_renewal',
                'maintenance',
                'other',
            ])->default('other');
            $table->nullableMorphs('related'); // related_type, related_id
            $table->date('due_date');
            $table->enum('status', ['pending', 'acknowledged', 'overdue', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_reminders');
    }
};
