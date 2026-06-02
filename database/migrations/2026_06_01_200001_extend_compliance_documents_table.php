<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->foreignId('lease_id')->nullable()->after('transaction_id')
                ->constrained('leases')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->after('lease_id')
                ->constrained('listings')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->after('listing_id')
                ->constrained('properties')->nullOnDelete();
            $table->enum('category', [
                'lease_agreement',
                'compliance_record',
                'inspection_report',
                'contract',
                'identity',
                'financial',
                'other',
            ])->default('other')->after('document_type');
            $table->text('notes')->nullable()->after('rejection_reason');

            $table->index(['agency_id', 'category']);
            $table->index(['agency_id', 'expiry_date']);
        });

        // Make transaction_id nullable so standalone docs are possible
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lease_id');
            $table->dropConstrainedForeignId('listing_id');
            $table->dropConstrainedForeignId('property_id');
            $table->dropColumn(['category', 'notes']);
        });
    }
};
