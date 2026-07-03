<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_requests', 'request_reason_type')) {
                $table->string('request_reason_type')->nullable();
            }

            if (! Schema::hasColumn('employee_schedule_requests', 'vacation_without_pay')) {
                $table->boolean('vacation_without_pay')->default(false);
            }
        });

        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_entries', 'request_reason_type')) {
                $table->string('request_reason_type')->nullable();
            }

            if (! Schema::hasColumn('employee_schedule_entries', 'vacation_without_pay')) {
                $table->boolean('vacation_without_pay')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_entries', 'vacation_without_pay')) {
                $table->dropColumn('vacation_without_pay');
            }

            if (Schema::hasColumn('employee_schedule_entries', 'request_reason_type')) {
                $table->dropColumn('request_reason_type');
            }
        });

        Schema::table('employee_schedule_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_requests', 'vacation_without_pay')) {
                $table->dropColumn('vacation_without_pay');
            }

            if (Schema::hasColumn('employee_schedule_requests', 'request_reason_type')) {
                $table->dropColumn('request_reason_type');
            }
        });
    }
};
