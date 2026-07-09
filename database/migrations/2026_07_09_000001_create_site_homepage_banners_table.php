<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_homepage_banners', function (Blueprint $table): void {
            $table->id();
            $table->string('eyebrow')->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('button_label')->default('Перейти');
            $table->string('link_type')->default('catalog');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->string('image_path')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->string('theme')->default('blue');
            $table->boolean('opens_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_homepage_banners');
    }
};
