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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('customer_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();

            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->string('delivery_status')->default('not_shipped')->index();

            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('email')->index();
            $table->string('phone')->index();

            $table->string('postal_code')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('house')->nullable();
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->string('entrance')->nullable();
            $table->string('floor')->nullable();
            $table->text('delivery_comment')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('delivery_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
