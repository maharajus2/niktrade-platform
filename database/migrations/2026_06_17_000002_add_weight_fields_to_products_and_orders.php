<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_value', 10, 3)->nullable()->after('volume_unit');
            $table->string('weight_unit')->nullable()->default('g')->after('weight_value');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('weight_snapshot_value', 10, 3)->nullable()->after('product_image_path');
            $table->string('weight_snapshot_unit')->nullable()->after('weight_snapshot_value');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('total_weight_grams')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_weight_grams');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'weight_snapshot_value',
                'weight_snapshot_unit',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'weight_value',
                'weight_unit',
            ]);
        });
    }
};
