<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('fulfillment_status')->default('not_sent')->index()->after('delivery_status');
            $table->timestamp('ready_for_dispatch_at')->nullable()->after('assembled_at')->index();
        });

        DB::table('orders')
            ->where('fulfillment_method', 'pickup')
            ->update([
                'fulfillment_status' => DB::raw("
                    CASE delivery_status
                        WHEN 'ready_for_pickup' THEN 'ready_for_pickup'
                        WHEN 'picked_up' THEN 'picked_up'
                        WHEN 'delivered' THEN 'picked_up'
                        ELSE 'not_ready'
                    END
                "),
            ]);

        DB::table('orders')
            ->where(function ($query): void {
                $query->whereNull('fulfillment_method')
                    ->orWhere('fulfillment_method', '!=', 'pickup');
            })
            ->update([
                'fulfillment_method' => 'delivery',
                'fulfillment_status' => DB::raw("
                    CASE delivery_status
                        WHEN 'shipped' THEN 'shipped'
                        WHEN 'delivered' THEN 'delivered'
                        WHEN 'picked_up' THEN 'delivered'
                        ELSE 'not_sent'
                    END
                "),
            ]);

        DB::table('orders')
            ->whereIn('status', ['assembled', 'handed_to_delivery'])
            ->update([
                'ready_for_dispatch_at' => DB::raw('COALESCE(assembled_at, handed_to_delivery_at, updated_at, created_at)'),
                'status' => 'ready_for_dispatch',
            ]);

        DB::table('orders')
            ->where('status', 'delivered')
            ->update([
                'ready_for_dispatch_at' => DB::raw('COALESCE(assembled_at, handed_to_delivery_at, delivered_at, updated_at, created_at)'),
                'status' => 'completed',
            ]);

        DB::table('orders')
            ->where('status', 'processing')
            ->update([
                'assembling_at' => DB::raw('COALESCE(assembling_at, updated_at, created_at)'),
                'status' => 'assembling',
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'fulfillment_status',
                'ready_for_dispatch_at',
            ]);
        });
    }
};
