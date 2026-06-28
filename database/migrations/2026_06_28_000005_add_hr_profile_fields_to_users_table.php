<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('emergency_contact');
            }

            if (! Schema::hasColumn('users', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('employment_type')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'employment_status')) {
                $table->string('employment_status')->default(User::STATUS_WORKING)->after('manager_id');
            }

            if (! Schema::hasColumn('users', 'probation_enabled')) {
                $table->boolean('probation_enabled')->default(false)->after('dismissal_date');
            }

            if (! Schema::hasColumn('users', 'probation_started_at')) {
                $table->date('probation_started_at')->nullable()->after('probation_enabled');
            }

            if (! Schema::hasColumn('users', 'probation_ends_at')) {
                $table->date('probation_ends_at')->nullable()->after('probation_started_at');
            }

            if (! Schema::hasColumn('users', 'probation_cancelled_at')) {
                $table->timestamp('probation_cancelled_at')->nullable()->after('probation_ends_at');
            }

            if (! Schema::hasColumn('users', 'probation_cancelled_by')) {
                $table->foreignId('probation_cancelled_by')->nullable()->after('probation_cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'salary_amount')) {
                $table->decimal('salary_amount', 12, 2)->nullable()->after('probation_cancelled_by');
            }

            if (! Schema::hasColumn('users', 'salary_currency')) {
                $table->string('salary_currency')->default('RUB')->after('salary_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'probation_cancelled_by')) {
                $table->dropConstrainedForeignId('probation_cancelled_by');
            }

            if (Schema::hasColumn('users', 'manager_id')) {
                $table->dropConstrainedForeignId('manager_id');
            }

            foreach ([
                'employment_type',
                'employment_status',
                'probation_enabled',
                'probation_started_at',
                'probation_ends_at',
                'probation_cancelled_at',
                'salary_amount',
                'salary_currency',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
