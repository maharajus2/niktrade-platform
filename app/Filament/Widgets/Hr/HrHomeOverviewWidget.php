<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleEntry;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class HrHomeOverviewWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.home-overview-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $today = today();
        $soon = today()->addDays(14);

        $pendingHrRequests = EmployeeScheduleRequest::query()
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->whereIn('status', [
                EmployeeScheduleRequest::STATUS_PENDING,
                EmployeeScheduleRequest::STATUS_IN_REVIEW,
                EmployeeScheduleRequest::STATUS_FORWARDED,
            ])
            ->whereHas('approvalWorkflow.currentApprover.roles', fn (Builder $query): Builder => $query->where('name', 'hr'))
            ->count();

        $documentsExpiring = EmployeeDocument::query()
            ->whereNull('archived_at')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $today->copy()->addDays(30))
            ->count();

        $employeesMissingDocuments = User::query()
            ->whereNull('archived_at')
            ->with('activeDocuments')
            ->get()
            ->filter(fn (User $employee): bool => $employee->missingRequiredDocuments() !== [])
            ->count();

        $probationEndingSoon = User::query()
            ->where('probation_enabled', true)
            ->whereNull('probation_cancelled_at')
            ->whereNotNull('probation_ends_at')
            ->whereBetween('probation_ends_at', [$today->toDateString(), $soon->toDateString()])
            ->count();

        $todayEntries = EmployeeScheduleEntry::query()
            ->hrVisible()
            ->whereDate('date', $today)
            ->get();

        $birthdays = User::query()
            ->with('department')
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function (User $employee): array {
                $birthday = $this->nextBirthday($employee);

                return [
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'date' => $birthday,
                    'url' => UserResource::getUrl('view', ['record' => $employee]),
                ];
            })
            ->sortBy('date')
            ->values();

        return [
            'requestUrl' => EmployeeScheduleRequestResource::getUrl('index'),
            'employeeUrl' => UserResource::getUrl('index'),
            'cards' => [
                [
                    'label' => 'Заявки HR',
                    'value' => $pendingHrRequests,
                    'description' => 'Ожидают решения HR',
                    'url' => EmployeeScheduleRequestResource::getUrl('index'),
                    'color' => 'warning',
                ],
                [
                    'label' => 'Документы',
                    'value' => $documentsExpiring + $employeesMissingDocuments,
                    'description' => 'Истекают или не хватает',
                    'url' => UserResource::getUrl('index'),
                    'color' => 'danger',
                ],
                [
                    'label' => 'Испытательный срок',
                    'value' => $probationEndingSoon,
                    'description' => 'Заканчивается в ближайшие 14 дней',
                    'url' => UserResource::getUrl('index'),
                    'color' => 'info',
                ],
                [
                    'label' => 'Сегодня отсутствуют',
                    'value' => $todayEntries
                        ->whereIn('type', [EmployeeScheduleEntry::TYPE_VACATION, EmployeeScheduleEntry::TYPE_SICK_LEAVE])
                        ->count(),
                    'description' => 'Отпуск / больничный сегодня',
                    'url' => UserResource::getUrl('index'),
                    'color' => 'success',
                ],
            ],
            'todayVacation' => $todayEntries->where('type', EmployeeScheduleEntry::TYPE_VACATION)->count(),
            'todaySickLeave' => $todayEntries->where('type', EmployeeScheduleEntry::TYPE_SICK_LEAVE)->count(),
            'birthdaysToday' => $birthdays->filter(fn (array $item): bool => $item['date']->isToday())->values()->all(),
            'birthdaysWeek' => $birthdays->filter(fn (array $item): bool => $item['date']->betweenIncluded(today(), today()->addDays(7)))->take(6)->values()->all(),
        ];
    }

    private function nextBirthday(User $employee): Carbon
    {
        $birthday = Carbon::parse($employee->date_of_birth)->year((int) today()->format('Y'));

        return $birthday->isBefore(today()) ? $birthday->addYear() : $birthday;
    }
}
