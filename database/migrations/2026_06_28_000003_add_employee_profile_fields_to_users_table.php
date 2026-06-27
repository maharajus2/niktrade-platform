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
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('phone')->nullable()->after('avatar_path');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('telegram_username')->nullable()->after('date_of_birth');
            $table->string('emergency_contact')->nullable()->after('telegram_username');
            $table->string('employee_status')->default(User::STATUS_WORKING)->after('emergency_contact');
            $table->date('hire_date')->nullable()->after('employee_status');
            $table->date('dismissal_date')->nullable()->after('hire_date');
            $table->string('schedule_type')->default(User::SCHEDULE_FIVE_TWO)->after('dismissal_date');
            $table->json('working_days')->nullable()->after('schedule_type');
            $table->time('work_starts_at')->nullable()->after('working_days');
            $table->time('work_ends_at')->nullable()->after('work_starts_at');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->timestamp('archived_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'phone',
                'date_of_birth',
                'telegram_username',
                'emergency_contact',
                'employee_status',
                'hire_date',
                'dismissal_date',
                'schedule_type',
                'working_days',
                'work_starts_at',
                'work_ends_at',
                'last_login_at',
                'archived_at',
            ]);
        });
    }
};
