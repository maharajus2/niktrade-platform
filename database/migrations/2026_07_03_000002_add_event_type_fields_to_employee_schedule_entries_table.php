<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_entries', 'type')) {
                $table->string('type')->default('shift')->after('employee_id');
            }

            if (! Schema::hasColumn('employee_schedule_entries', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (! Schema::hasColumn('employee_schedule_entries', 'is_all_day')) {
                $table->boolean('is_all_day')->default(false)->after('title');
            }
        });

        DB::statement('ALTER TABLE employee_schedule_entries MODIFY starts_at TIME NULL');
        DB::statement('ALTER TABLE employee_schedule_entries MODIFY ends_at TIME NULL');

        DB::table('employee_schedule_entries')
            ->whereNull('type')
            ->update(['type' => 'shift']);

        DB::table('employee_schedule_entries')
            ->whereNull('is_all_day')
            ->update(['is_all_day' => false]);
    }

    public function down(): void
    {
        DB::table('employee_schedule_entries')
            ->whereNull('starts_at')
            ->update(['starts_at' => '00:00:00']);

        DB::table('employee_schedule_entries')
            ->whereNull('ends_at')
            ->update(['ends_at' => '23:59:00']);

        DB::statement('ALTER TABLE employee_schedule_entries MODIFY starts_at TIME NOT NULL');
        DB::statement('ALTER TABLE employee_schedule_entries MODIFY ends_at TIME NOT NULL');

        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_entries', 'is_all_day')) {
                $table->dropColumn('is_all_day');
            }

            if (Schema::hasColumn('employee_schedule_entries', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('employee_schedule_entries', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
