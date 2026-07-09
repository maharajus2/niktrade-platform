<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_homepage_banners', function (Blueprint $table): void {
            $table->string('text_color', 20)->nullable()->after('theme');
            $table->string('font_family', 40)->default('default')->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('site_homepage_banners', function (Blueprint $table): void {
            $table->dropColumn(['text_color', 'font_family']);
        });
    }
};
