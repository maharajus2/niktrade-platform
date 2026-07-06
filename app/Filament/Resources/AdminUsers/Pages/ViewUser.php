<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Pages\MyCalendar;
use App\Filament\Pages\Workplace;
use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleEntry;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.admin-users.pages.employee-profile';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Просмотр сотрудника';

    protected static ?string $breadcrumb = 'Просмотр сотрудника';

    public function getTitle(): string|Htmlable
    {
        return $this->record instanceof User ? $this->record->name : 'Сотрудник';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => UserResource::canEdit($this->record)),
            UserResource::archiveAction(),
        ];
    }

    protected function getViewData(): array
    {
        /** @var User $employee */
        $employee = $this->record;
        $employee->loadMissing(['department.manager', 'department.actingManager', 'manager', 'roles']);

        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $todayEntries = UserResource::canViewEmployeeSchedule($employee)
            ? $employee->scheduleEntries()
                ->whereDate('date', $today)
                ->orderBy('starts_at')
                ->get()
            : collect();

        $monthEntries = UserResource::canViewEmployeeSchedule($employee)
            ? $employee->scheduleEntries()
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orderBy('date')
                ->get()
                ->groupBy(fn (EmployeeScheduleEntry $entry): string => $entry->date->toDateString())
            : collect();

        $recentRequests = UserResource::canViewEmployeeSchedule($employee)
            ? $employee->scheduleRequests()
                ->whereNull('deleted_at')
                ->latest()
                ->take(4)
                ->get()
            : collect();

        $activeDocuments = UserResource::canViewEmployeeDocuments($employee)
            ? $employee->activeDocuments()->latest()->take(5)->get()
            : collect();

        $expiringDocuments = UserResource::canViewEmployeeDocuments($employee)
            ? $employee->expiringDocuments()
            : collect();

        $documentData = UserResource::canViewEmployeeDocuments($employee)
            ? UserResource::employeeDocumentsDashboardData($employee)
            : [
                'completenessPercent' => 0,
                'missingCount' => 0,
                'expiringCount' => 0,
                'expiredCount' => 0,
                'missingDocumentLabels' => [],
            ];

        return [
            'profile' => [
                'employee' => $employee,
                'photoUrl' => $employee->avatar_path ? Storage::disk('public')->url($employee->avatar_path) : null,
                'statusColor' => User::employeeStatusColor($employee->employment_status ?? $employee->employee_status),
                'statusLabel' => $employee->getEmploymentStatusLabel(),
                'roleLabel' => AdminRoles::primaryLabel($employee),
                'manager' => $employee->getEffectiveManager(),
                'canEdit' => UserResource::canEdit($employee),
                'canViewDocuments' => UserResource::canViewEmployeeDocuments($employee),
                'canViewSchedule' => UserResource::canViewEmployeeSchedule($employee),
                'canViewAccess' => UserResource::canManageEmployeeRoles(),
                'canViewSalary' => UserResource::canViewSalary($employee),
                'canViewCitizenship' => UserResource::canViewCitizenshipProfile($employee),
                'editUrl' => UserResource::getUrl('edit', ['record' => $employee]),
                'listUrl' => UserResource::getUrl('index'),
                'calendarUrl' => MyCalendar::getUrl(),
                'requestsUrl' => EmployeeScheduleRequestResource::getUrl('index'),
                'createRequestUrl' => EmployeeScheduleRequestResource::getUrl('create'),
                'documentsUrl' => '#employee-profile-documents',
                'workplaceUrl' => Workplace::getUrl(),
                'monthLabel' => $monthStart->translatedFormat('F Y'),
                'todayStatus' => $this->employeeStatusLabel($todayEntries),
                'shiftLabel' => $this->shiftLabel($todayEntries) ?: $employee->getScheduleTypeLabel(),
                'calendarDays' => $this->calendarDays($monthStart, $monthEnd, $monthEntries),
                'recentScheduleEntries' => $this->timeline($todayEntries),
                'recentRequests' => $recentRequests,
                'requestCounts' => $this->requestCounts($employee),
                'documents' => [
                    'completeness' => $documentData['completenessPercent'],
                    'missingCount' => $documentData['missingCount'],
                    'expiringCount' => $documentData['expiringCount'],
                    'expiredCount' => $documentData['expiredCount'],
                    'missingDocumentLabels' => $documentData['missingDocumentLabels'],
                    'items' => $activeDocuments,
                    'expiringItems' => $expiringDocuments,
                ],
                'attentionItems' => $this->attentionItems($employee, $expiringDocuments),
                'mobileMenuGroups' => $this->mobileMenuGroups($employee),
            ],
        ];
    }

    private function employeeStatusLabel(Collection $entries): string
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

    private function shiftLabel(Collection $entries): ?string
    {
        $shifts = $entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT);

        return $shifts->isNotEmpty()
            ? $shifts->map(fn (EmployeeScheduleEntry $entry): string => $entry->timeLabel())->join(', ')
            : null;
    }

    private function timeline(Collection $entries): array
    {
        return $entries
            ->map(fn (EmployeeScheduleEntry $entry): array => [
                'time' => $entry->is_all_day ? 'Весь день' : $entry->timeLabel(),
                'title' => $entry->getDisplayTitle(),
                'meta' => $entry->comment ?: 'Событие календаря',
                'color' => $entry->getCalendarColor(),
            ])
            ->values()
            ->all();
    }

    private function calendarDays($monthStart, $monthEnd, Collection $entries): array
    {
        $days = [];
        $cursor = $monthStart->copy()->startOfWeek();
        $last = $monthEnd->copy()->endOfWeek();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $monthStart->month,
                'isToday' => $cursor->isToday(),
                'events' => $entries->get($key, collect())->take(4)->values(),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    private function requestCounts(User $employee): array
    {
        if (! UserResource::canViewEmployeeSchedule($employee)) {
            return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'returned' => 0];
        }

        return [
            'pending' => $employee->scheduleRequests()
                ->whereIn('status', [
                    EmployeeScheduleRequest::STATUS_PENDING,
                    EmployeeScheduleRequest::STATUS_IN_REVIEW,
                    EmployeeScheduleRequest::STATUS_FORWARDED,
                ])
                ->whereNull('deleted_at')
                ->count(),
            'approved' => $employee->scheduleRequests()->where('status', EmployeeScheduleRequest::STATUS_APPROVED)->whereNull('deleted_at')->count(),
            'rejected' => $employee->scheduleRequests()->where('status', EmployeeScheduleRequest::STATUS_REJECTED)->whereNull('deleted_at')->count(),
            'returned' => $employee->scheduleRequests()->where('status', EmployeeScheduleRequest::STATUS_RETURNED)->whereNull('deleted_at')->count(),
        ];
    }

    private function attentionItems(User $employee, Collection $expiringDocuments): array
    {
        $items = collect();

        $expiringDocuments
            ->take(2)
            ->each(fn (EmployeeDocument $document) => $items->push([
                'tone' => $document->isExpired() ? 'red' : 'amber',
                'title' => $document->isExpired() ? 'Документ просрочен' : 'Документ скоро истекает',
                'text' => $document->getCategoryLabel(),
            ]));

        if ($employee->probation_enabled && $employee->probation_ends_at?->betweenIncluded(today(), today()->addDays(14))) {
            $items->push([
                'tone' => 'blue',
                'title' => 'Испытательный срок',
                'text' => 'Заканчивается '.$employee->probation_ends_at->format('d.m.Y'),
            ]);
        }

        return $items->take(3)->values()->all();
    }

    private function mobileMenuGroups(User $employee): array
    {
        return [
            'Главное' => [
                ['label' => 'Главная', 'icon' => 'home', 'url' => Workplace::getUrl()],
                ['label' => 'Сотрудники', 'icon' => 'users', 'url' => UserResource::getUrl('index'), 'active' => true],
                ['label' => 'Календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
                ['label' => 'Документы', 'icon' => 'file', 'url' => '#employee-profile-documents-mobile'],
            ],
            'Профиль сотрудника' => [
                ['label' => 'Данные', 'icon' => 'users', 'url' => '#employee-profile-about-mobile'],
                ['label' => 'Заявки', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index')],
                ['label' => 'Задачи', 'icon' => 'check-square', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
                ['label' => 'Доступы', 'icon' => 'grid', 'url' => '#employee-profile-access', 'badge' => UserResource::canManageEmployeeRoles() ? null : 'Скоро', 'disabled' => ! UserResource::canManageEmployeeRoles()],
            ],
            'Компания' => array_values(array_filter([
                UserResource::canAccess() ? ['label' => 'Все сотрудники', 'icon' => 'users', 'url' => UserResource::getUrl('index')] : null,
                DepartmentResource::canAccess() ? ['label' => 'Организация', 'icon' => 'building', 'url' => DepartmentResource::getUrl('index')] : null,
                UserResource::canEdit($employee) ? ['label' => 'Редактировать', 'icon' => 'file', 'url' => UserResource::getUrl('edit', ['record' => $employee])] : null,
            ])),
        ];
    }
}
