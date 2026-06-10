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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_line_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('article')->nullable();

            $table->text('short_description')->nullable();
            $table->text('description')->nullable();

            $table->text('composition')->nullable();
            $table->text('usage_method')->nullable();
            $table->text('storage_conditions')->nullable();

            $table->unsignedInteger('shelf_life_value')->nullable();
            $table->string('shelf_life_unit')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_best_seller')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
