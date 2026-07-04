<?php

namespace App\Filament\Widgets\Employee;

use App\Models\EmployeeScheduleEntry;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class EmployeeMyDayWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.my-day-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        /** @var User|null $employee */
        $employee = auth()->user();
        $today = today();

        $entries = $employee?->scheduleEntries()
            ->whereDate('date', $today)
            ->orderBy('starts_at')
            ->get() ?? collect();

        $shifts = $entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT);
        $hours = $this->plannedHours($shifts);

        return [
            'dateLabel' => $today->translatedFormat('l, d F Y'),
            'statusLabel' => $this->statusLabel($entries),
            'shiftLabel' => $shifts->isNotEmpty()
                ? $shifts->map(fn (EmployeeScheduleEntry $entry): string => $entry->timeLabel())->join(', ')
                : null,
            'hoursToday' => $hours,
            'timeline' => $this->timeline($entries),
        ];
    }

    private function statusLabel(Collection $entries): string
    {
        if ($entries->where('type', EmployeeScheduleEntry::TYPE_VACATION)->isNotEmpty()) {
            return 'Отпуск';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_SICK_LEAVE)->isNotEmpty()) {
            return 'Больничный';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_DAY_OFF)->isNotEmpty()) {
            return 'Выходной';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT)->isNotEmpty()) {
            return 'Рабочий день';
        }

        return 'Смен не назначено';
    }

    private function plannedHours(Collection $shifts): float
    {
        return round($shifts
            ->filter(fn (EmployeeScheduleEntry $entry): bool => $entry->isWorkTime())
            ->sum(function (EmployeeScheduleEntry $entry): float {
                $start = strtotime((string) $entry->starts_at);
                $end = strtotime((string) $entry->ends_at);

                if ($start === false || $end === false || $end <= $start) {
                    return 0;
                }

                return ($end - $start) / 3600;
            }), 1);
    }

    private function timeline(Collection $entries): array
    {
        $items = [];

        foreach ($entries as $entry) {
            if ($entry->type === EmployeeScheduleEntry::TYPE_SHIFT && $entry->starts_at && $entry->ends_at) {
                $items[] = ['time' => substr((string) $entry->starts_at, 0, 5), 'title' => 'Начало смены'];

                if (substr((string) $entry->starts_at, 0, 5) <= '13:00' && substr((string) $entry->ends_at, 0, 5) >= '14:00') {
                    $items[] = ['time' => '13:00', 'title' => 'Обед'];
                }

                $items[] = ['time' => substr((string) $entry->ends_at, 0, 5), 'title' => 'Конец смены'];

                continue;
            }

            $items[] = [
                'time' => $entry->is_all_day ? 'Весь день' : $entry->timeLabel(),
                'title' => $entry->getDisplayTitle(),
            ];
        }

        return collect($items)
            ->sortBy('time')
            ->values()
            ->all();
    }
}
