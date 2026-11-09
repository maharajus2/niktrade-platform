<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['contractor_id', 'product_id']);
        });
        Schema::create('business_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('contractor_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('new')->index();
            $table->text('comment')->nullable();
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->unsignedInteger('items_count');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_path')->nullable();
            $table->timestamp('invoice_issued_at')->nullable();
            $table->text('manager_comment')->nullable();
            $table->timestamps();
        });
        Schema::create('business_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('article')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('list_price', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 12, 2);
        });
        Schema::create('business_request_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_request_status_logs');
        Schema::dropIfExists('business_request_items');
        Schema::dropIfExists('business_requests');
        Schema::dropIfExists('business_cart_items');
    }
};
