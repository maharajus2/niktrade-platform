<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('inn', 12)->index();
            $table->string('kpp', 9)->nullable();
            $table->string('ogrn', 15)->nullable();
            $table->text('legal_address')->nullable();
            $table->string('director')->nullable();
            $table->string('contact_name');
            $table->string('phone', 50);
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_code')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->index(['inn', 'kpp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
