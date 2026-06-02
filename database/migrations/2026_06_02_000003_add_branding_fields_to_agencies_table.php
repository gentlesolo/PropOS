<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('favicon_path')->nullable()->after('logo_path');
            $table->string('font_family')->nullable()->after('accent_color');
            $table->string('border_radius', 20)->default('default')->after('font_family');
            $table->string('sidebar_style', 20)->default('dark')->after('border_radius');
            $table->text('custom_css')->nullable()->after('sidebar_style');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['favicon_path', 'font_family', 'border_radius', 'sidebar_style', 'custom_css']);
        });
    }
};
