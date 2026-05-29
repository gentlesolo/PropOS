<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_activities', function (Blueprint $table) {
            $table->foreignId('deal_id')->nullable()->after('contact_id')->constrained('deals')->nullOnDelete();
            $table->index('deal_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_activities', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropColumn('deal_id');
        });
    }
};
