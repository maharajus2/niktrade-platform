<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('fulfillment_method')->default('delivery')->index()->after('customer_address_id');
            $table->foreignId('warehouse_id')->nullable()->after('fulfillment_method')->constrained()->nullOnDelete();
            $table->string('warehouse_name_snapshot')->nullable()->after('warehouse_id');
            $table->string('warehouse_address_snapshot')->nullable()->after('warehouse_name_snapshot');
            $table->string('warehouse_phone_snapshot')->nullable()->after('warehouse_address_snapshot');
            $table->string('warehouse_working_hours_snapshot')->nullable()->after('warehouse_phone_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn([
                'fulfillment_method',
                'warehouse_id',
                'warehouse_name_snapshot',
                'warehouse_address_snapshot',
                'warehouse_phone_snapshot',
                'warehouse_working_hours_snapshot',
            ]);
        });
    }
};
