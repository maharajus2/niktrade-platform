<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('precautions')->nullable()->after('storage_conditions');
            $table->text('disposal_method')->nullable()->after('precautions');
            $table->string('availability_status')->default('in_stock')->after('disposal_method');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'precautions',
                'disposal_method',
                'availability_status',
            ]);
        });
    }
};
