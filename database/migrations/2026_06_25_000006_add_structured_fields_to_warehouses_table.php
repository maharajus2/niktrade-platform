<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->string('postal_code')->nullable()->after('address');
            $table->string('region')->nullable()->after('postal_code');
            $table->string('street')->nullable()->after('city');
            $table->string('house')->nullable()->after('street');
            $table->string('building')->nullable()->after('house');
            $table->string('premises')->nullable()->after('building');
            $table->json('working_days')->nullable()->after('working_hours');
            $table->string('working_time_from')->nullable()->after('working_days');
            $table->string('working_time_to')->nullable()->after('working_time_from');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->dropColumn([
                'postal_code',
                'region',
                'street',
                'house',
                'building',
                'premises',
                'working_days',
                'working_time_from',
                'working_time_to',
            ]);
        });
    }
};
