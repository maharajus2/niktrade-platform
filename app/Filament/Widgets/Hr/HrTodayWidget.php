<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Models\EmployeeScheduleEntry;
use Filament\Widgets\Widget;

class HrTodayWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.today-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        $today = today();
        $entries = EmployeeScheduleEntry::query()
            ->active()
            ->hrVisible()
            ->with('employee.department')
            ->whereDate('date', $today)
            ->orderBy('starts_at')
            ->get();

        return [
            'dateLabel' => $today->translatedFormat('d F Y'),
            'calendarUrl' => UserResource::getUrl('index'),
            'stats' => [
                ['label' => 'Работают', 'value' => $entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT)->count(), 'color' => 'success'],
                ['label' => 'Отпуск', 'value' => $entries->where('type', EmployeeScheduleEntry::TYPE_VACATION)->count(), 'color' => 'purple'],
                ['label' => 'Больничный', 'value' => $entries->where('type', EmployeeScheduleEntry::TYPE_SICK_LEAVE)->count(), 'color' => 'warning'],
                ['label' => 'Выходной', 'value' => $entries->where('type', EmployeeScheduleEntry::TYPE_DAY_OFF)->count(), 'color' => 'gray'],
            ],
            'events' => $entries
                ->take(6)
                ->map(fn (EmployeeScheduleEntry $entry): array => [
                    'title' => $entry->getDisplayTitle(),
                    'employee' => $entry->employee?->name ?? 'Сотрудник',
                    'department' => $entry->employee?->department?->name,
                    'url' => $entry->employee ? UserResource::getUrl('view', ['record' => $entry->employee]) : null,
                    'color' => $entry->getCalendarColor(),
                ])
                ->values()
                ->all(),
        ];
    }
}
