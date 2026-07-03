<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_requests', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->index();
            }

            if (! Schema::hasColumn('employee_schedule_requests', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_schedule_requests', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->index();
            }

            if (! Schema::hasColumn('employee_schedule_requests', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_requests', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }

            if (Schema::hasColumn('employee_schedule_requests', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }

            if (Schema::hasColumn('employee_schedule_requests', 'archived_by')) {
                $table->dropConstrainedForeignId('archived_by');
            }

            if (Schema::hasColumn('employee_schedule_requests', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
