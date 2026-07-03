<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_entries', 'visibility')) {
                $table->string('visibility')->default('hr')->index();
            }

            if (! Schema::hasColumn('employee_schedule_entries', 'source')) {
                $table->string('source')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_entries', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('employee_schedule_entries', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
