<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_schedule_entries', 'period_group_id')) {
                $table->uuid('period_group_id')->nullable()->after('approved_request_id')->index();
            }
        });

        $this->backfillExistingPeriods();
    }

    public function down(): void
    {
        Schema::table('employee_schedule_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_schedule_entries', 'period_group_id')) {
                $table->dropColumn('period_group_id');
            }
        });
    }

    private function backfillExistingPeriods(): void
    {
        $entries = DB::table('employee_schedule_entries')
            ->select([
                'id',
                'employee_id',
                'type',
                'title',
                'is_all_day',
                'date',
                'starts_at',
                'ends_at',
                'request_reason_type',
                'vacation_without_pay',
                'comment',
                'visibility',
                'source',
                'approved_request_id',
            ])
            ->whereNull('period_group_id')
            ->whereNull('archived_at')
            ->where('type', '!=', 'shift')
            ->orderBy('employee_id')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $entry): string => $this->periodSignature($entry));

        foreach ($entries as $group) {
            $run = collect();
            $previousDate = null;

            foreach ($group as $entry) {
                $date = Carbon::parse($entry->date)->toDateString();

                if ($previousDate !== null && Carbon::parse($previousDate)->addDay()->toDateString() !== $date) {
                    $this->assignPeriodGroup($run);
                    $run = collect();
                }

                $run->push($entry);
                $previousDate = $date;
            }

            $this->assignPeriodGroup($run);
        }
    }

    private function assignPeriodGroup($entries): void
    {
        if ($entries->count() < 2) {
            return;
        }

        DB::table('employee_schedule_entries')
            ->whereIn('id', $entries->pluck('id')->all())
            ->update(['period_group_id' => (string) Str::uuid()]);
    }

    private function periodSignature(object $entry): string
    {
        return json_encode([
            'employee_id' => $entry->employee_id,
            'type' => $entry->type,
            'title' => $entry->title,
            'is_all_day' => (bool) $entry->is_all_day,
            'starts_at' => $entry->starts_at,
            'ends_at' => $entry->ends_at,
            'request_reason_type' => $entry->request_reason_type,
            'vacation_without_pay' => (bool) $entry->vacation_without_pay,
            'comment' => $entry->comment,
            'visibility' => $entry->visibility,
            'source' => $entry->source,
            'approved_request_id' => $entry->approved_request_id,
        ]);
    }
};
