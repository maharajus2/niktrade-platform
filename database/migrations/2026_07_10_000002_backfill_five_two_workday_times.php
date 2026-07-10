<?php

use App\Models\User;
use App\Support\EmployeeWorkday;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->whereNull('work_starts_at')
            ->update(['work_starts_at' => EmployeeWorkday::DEFAULT_START]);

        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->whereNull('work_ends_at')
            ->update(['work_ends_at' => EmployeeWorkday::DEFAULT_END]);

        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->whereNull('lunch_starts_at')
            ->update(['lunch_starts_at' => EmployeeWorkday::DEFAULT_LUNCH_START]);

        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->whereNull('lunch_ends_at')
            ->update(['lunch_ends_at' => EmployeeWorkday::DEFAULT_LUNCH_END]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->where('lunch_starts_at', EmployeeWorkday::DEFAULT_LUNCH_START)
            ->update(['lunch_starts_at' => null]);

        DB::table('users')
            ->where('schedule_type', User::SCHEDULE_FIVE_TWO)
            ->where('lunch_ends_at', EmployeeWorkday::DEFAULT_LUNCH_END)
            ->update(['lunch_ends_at' => null]);
    }
};
