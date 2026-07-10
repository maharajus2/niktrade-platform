<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

class EmployeeWorkday
{
    public const DEFAULT_START = '09:00';

    public const DEFAULT_END = '18:00';

    public const DEFAULT_LUNCH_START = '13:00';

    public const DEFAULT_LUNCH_END = '14:00';

    /**
     * @return array{
     *     isConfiguredSchedule: bool,
     *     isWorkday: bool,
     *     isWeekend: bool,
     *     isHoliday: bool,
     *     isShortened: bool,
     *     holidayLabel: ?string,
     *     startsAt: ?string,
     *     endsAt: ?string,
     *     baseEndsAt: ?string,
     *     lunchStartsAt: ?string,
     *     lunchEndsAt: ?string,
     *     plannedMinutes: int,
     *     elapsedPercent: int,
     *     hoursLabel: string,
     *     lunchLabel: string,
     *     statusLabel: string,
     *     note: ?string
     * }
     */
    public static function for(User $employee, Carbon|string|null $date = null): array
    {
        $date = $date instanceof Carbon ? $date->copy() : ($date ? Carbon::parse($date) : today());
        $isFiveTwo = $employee->schedule_type === User::SCHEDULE_FIVE_TWO;
        $holidayLabel = ProductionCalendar::holidayLabel($date);
        $isWeekend = $date->isWeekend();
        $isHoliday = $holidayLabel !== null;
        $isWorkday = $isFiveTwo && ! $isWeekend && ! $isHoliday;

        $start = self::normalizeTime($employee->work_starts_at) ?? self::DEFAULT_START;
        $baseEnd = self::normalizeTime($employee->work_ends_at) ?? self::DEFAULT_END;
        $lunchStart = self::normalizeTime($employee->lunch_starts_at) ?? self::DEFAULT_LUNCH_START;
        $lunchEnd = self::normalizeTime($employee->lunch_ends_at) ?? self::DEFAULT_LUNCH_END;
        $isShortened = $isWorkday && ProductionCalendar::isPreHoliday($date);
        $end = $isShortened ? self::shiftTime($baseEnd, -60) : $baseEnd;
        $plannedMinutes = $isWorkday ? max(0, self::minutesBetween($start, $end)) : 0;

        return [
            'isConfiguredSchedule' => $isFiveTwo,
            'isWorkday' => $isWorkday,
            'isWeekend' => $isWeekend,
            'isHoliday' => $isHoliday,
            'isShortened' => $isShortened,
            'holidayLabel' => $holidayLabel,
            'startsAt' => $isWorkday ? $start : null,
            'endsAt' => $isWorkday ? $end : null,
            'baseEndsAt' => $isWorkday ? $baseEnd : null,
            'lunchStartsAt' => $isWorkday ? $lunchStart : null,
            'lunchEndsAt' => $isWorkday ? $lunchEnd : null,
            'plannedMinutes' => $plannedMinutes,
            'elapsedPercent' => self::elapsedPercent($date, $start, $end, $isWorkday),
            'hoursLabel' => $isFiveTwo ? "{$start}-{$baseEnd}" : 'Не указан',
            'lunchLabel' => $isFiveTwo ? "{$lunchStart}-{$lunchEnd}" : 'Не указан',
            'statusLabel' => self::statusLabel($isFiveTwo, $isWorkday, $isWeekend, $isHoliday),
            'note' => self::note($isWorkday, $isShortened, $holidayLabel),
        ];
    }

    public static function normalizeTime(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        if (! filled($value)) {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', (string) $value, $matches) !== 1) {
            return null;
        }

        return str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT).':'.$matches[2];
    }

    public static function minutesBetween(?string $start, ?string $end): int
    {
        $startMinutes = self::timeToMinutes($start);
        $endMinutes = self::timeToMinutes($end);

        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            return 0;
        }

        return $endMinutes - $startMinutes;
    }

    public static function timeToMinutes(?string $value): ?int
    {
        $value = self::normalizeTime($value);

        if ($value === null) {
            return null;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $value));

        return ($hours * 60) + $minutes;
    }

    private static function shiftTime(string $time, int $minutes): string
    {
        return Carbon::createFromFormat('H:i', $time)->addMinutes($minutes)->format('H:i');
    }

    private static function elapsedPercent(Carbon $date, string $start, string $end, bool $isWorkday): int
    {
        if (! $isWorkday || ! $date->isToday()) {
            return 0;
        }

        $startAt = $date->copy()->setTimeFromTimeString($start);
        $endAt = $date->copy()->setTimeFromTimeString($end);

        if (now()->lessThanOrEqualTo($startAt)) {
            return 0;
        }

        if (now()->greaterThanOrEqualTo($endAt)) {
            return 100;
        }

        $total = max(1, $startAt->diffInSeconds($endAt));
        $elapsed = $startAt->diffInSeconds(now());

        return (int) max(0, min(100, round(($elapsed / $total) * 100)));
    }

    private static function statusLabel(bool $isFiveTwo, bool $isWorkday, bool $isWeekend, bool $isHoliday): string
    {
        if (! $isFiveTwo) {
            return 'Смены по календарю';
        }

        if ($isHoliday) {
            return 'Праздничный день';
        }

        if ($isWeekend) {
            return 'Выходной';
        }

        return $isWorkday ? 'Рабочий день' : 'Выходной';
    }

    private static function note(bool $isWorkday, bool $isShortened, ?string $holidayLabel): ?string
    {
        if ($isShortened) {
            return 'Сокращённый предпраздничный день: окончание на час раньше.';
        }

        if ($holidayLabel !== null) {
            return $holidayLabel;
        }

        return $isWorkday ? null : 'По графику 5/2 сегодня нерабочий день.';
    }
}
