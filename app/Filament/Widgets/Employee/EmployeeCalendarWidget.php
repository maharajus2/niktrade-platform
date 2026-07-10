<?php

namespace App\Filament\Widgets\Employee;

use App\Filament\Pages\MyCalendar;
use App\Models\EmployeeScheduleEntry;
use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.calendar-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        /** @var User|null $employee */
        $employee = auth()->user();
        $start = today()->startOfMonth();
        $end = today()->endOfMonth();

        $entries = $employee?->scheduleEntries()
            ->active()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy(fn (EmployeeScheduleEntry $entry): string => $entry->date->toDateString()) ?? collect();

        $days = [];
        $cursor = $start->copy()->startOfWeek();
        $last = $end->copy()->endOfWeek();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $start->month,
                'isToday' => $cursor->isToday(),
                'events' => $entries->get($key, collect())->take(3)->values(),
            ];

            $cursor->addDay();
        }

        return [
            'monthLabel' => $start->translatedFormat('F Y'),
            'days' => $days,
            'calendarUrl' => MyCalendar::getUrl(),
        ];
    }
}
