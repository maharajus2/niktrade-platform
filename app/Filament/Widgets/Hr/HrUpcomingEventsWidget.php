<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleEntry;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class HrUpcomingEventsWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.upcoming-events-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        $start = today();
        $end = today()->addDays(7);
        $events = collect();

        EmployeeScheduleEntry::query()
            ->hrVisible()
            ->with('employee.department')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('type', [EmployeeScheduleEntry::TYPE_VACATION, EmployeeScheduleEntry::TYPE_SICK_LEAVE, EmployeeScheduleEntry::TYPE_DAY_OFF])
            ->orderBy('date')
            ->limit(12)
            ->get()
            ->each(fn (EmployeeScheduleEntry $entry) => $events->push([
                'date' => $entry->date,
                'label' => $entry->getDisplayTitle(),
                'description' => $entry->employee?->name,
                'url' => $entry->employee ? UserResource::getUrl('view', ['record' => $entry->employee]) : null,
                'color' => $entry->type === EmployeeScheduleEntry::TYPE_VACATION ? 'purple' : 'warning',
            ]));

        User::query()
            ->whereNotNull('probation_ends_at')
            ->whereBetween('probation_ends_at', [$start->toDateString(), $end->toDateString()])
            ->orderBy('probation_ends_at')
            ->limit(8)
            ->get()
            ->each(fn (User $employee) => $events->push([
                'date' => $employee->probation_ends_at,
                'label' => 'Окончание испытательного срока',
                'description' => $employee->name,
                'url' => UserResource::getUrl('view', ['record' => $employee]),
                'color' => 'warning',
            ]));

        EmployeeDocument::query()
            ->with('employee')
            ->whereNull('archived_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$start->toDateString(), $end->toDateString()])
            ->orderBy('expires_at')
            ->limit(10)
            ->get()
            ->each(fn (EmployeeDocument $document) => $events->push([
                'date' => $document->expires_at,
                'label' => 'Истекает документ: '.$document->getCategoryLabel(),
                'description' => $document->employee?->name,
                'url' => $document->employee ? UserResource::getUrl('view', ['record' => $document->employee]) : null,
                'color' => 'danger',
            ]));

        User::query()
            ->whereNotNull('hire_date')
            ->whereBetween('hire_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('hire_date')
            ->limit(5)
            ->get()
            ->each(fn (User $employee) => $events->push([
                'date' => $employee->hire_date,
                'label' => 'Выход сотрудника',
                'description' => $employee->name,
                'url' => UserResource::getUrl('view', ['record' => $employee]),
                'color' => 'success',
            ]));

        User::query()
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(fn (User $employee): bool => $this->nextBirthday($employee)->betweenIncluded($start, $end))
            ->take(5)
            ->each(fn (User $employee) => $events->push([
                'date' => $this->nextBirthday($employee),
                'label' => 'День рождения',
                'description' => $employee->name,
                'url' => UserResource::getUrl('view', ['record' => $employee]),
                'color' => 'info',
            ]));

        return [
            'events' => $events
                ->sortBy('date')
                ->take(12)
                ->values()
                ->all(),
        ];
    }

    private function nextBirthday(User $employee): Carbon
    {
        $birthday = Carbon::parse($employee->date_of_birth)->year((int) today()->format('Y'));

        return $birthday->isBefore(today()) ? $birthday->addYear() : $birthday;
    }
}
