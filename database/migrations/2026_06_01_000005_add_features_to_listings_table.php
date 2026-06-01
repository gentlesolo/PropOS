<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('virtual_tour_url')->nullable();
            $table->string('virtual_tour_type')->nullable(); // youtube, matterport, custom
            $table->boolean('is_pocket')->default(false);
            $table->string('pocket_token', 64)->nullable()->unique();
            $table->string('mls_id')->nullable()->index();
            $table->timestamp('mls_last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'virtual_tour_url',
                'virtual_tour_type',
                'is_pocket',
                'pocket_token',
                'mls_id',
                'mls_last_synced_at',
            ]);
        });
    }
};
