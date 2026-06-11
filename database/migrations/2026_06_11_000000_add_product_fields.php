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
        Schema::table('products', function (Blueprint $table) {
            // Make product_line_id nullable
            $table->unsignedBigInteger('product_line_id')->nullable()->change();

            // New fields
            $table->unsignedInteger('volume_value')->nullable();
            $table->string('volume_unit')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('discount_percent')->nullable();
            $table->string('direction')->nullable();
            $table->string('instruction_file_path')->nullable();
            $table->string('barcode')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert product_line_id change (make it not nullable again)
            $table->unsignedBigInteger('product_line_id')->nullable(false)->change();

            // Drop new columns
            $table->dropColumn([
                'volume_value',
                'volume_unit',
                'price',
                'discount_percent',
                'direction',
                'instruction_file_path',
                'barcode',
            ]);
        });
    }
};
