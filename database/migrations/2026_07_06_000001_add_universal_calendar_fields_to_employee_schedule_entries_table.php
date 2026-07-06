<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_entries', 'approved_request_id')) {
                $table->foreignId('approved_request_id')
                    ->nullable()
                    ->after('source')
                    ->constrained('employee_schedule_requests')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_schedule_entries', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('approved_request_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_entries', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('employee_schedule_entries', 'approved_request_id')) {
                $table->dropConstrainedForeignId('approved_request_id');
            }
        });
    }
};
