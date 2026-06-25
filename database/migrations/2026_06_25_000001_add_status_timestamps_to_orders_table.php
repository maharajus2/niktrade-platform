<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('assembling_at')->nullable()->after('status');
            $table->timestamp('assembled_at')->nullable()->after('assembling_at');
            $table->timestamp('handed_to_delivery_at')->nullable()->after('assembled_at');
            $table->timestamp('delivered_at')->nullable()->after('handed_to_delivery_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'assembling_at',
                'assembled_at',
                'handed_to_delivery_at',
                'delivered_at',
                'cancelled_at',
            ]);
        });
    }
};
