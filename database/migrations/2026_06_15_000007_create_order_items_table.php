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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('product_name');
            $table->string('product_article')->nullable();
            $table->string('product_slug')->nullable();
            $table->string('product_image_path')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discounted_unit_price', 12, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 12, 2);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
