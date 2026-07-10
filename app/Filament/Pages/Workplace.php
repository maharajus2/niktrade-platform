<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleEntry;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use App\Support\Dashboard\DashboardWidgetRegistry;
use App\Support\EmployeeWorkday;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Workplace extends Page
{
    protected static string $routePath = '/workplace';

    protected string $view = 'filament.pages.workplace';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Рабочее пространство';

    protected static ?string $navigationLabel = 'Рабочее пространство';

    protected static ?int $navigationSort = -1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public function getTitle(): string|Htmlable
    {
        return 'Рабочее пространство';
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
        return [];
    }

    public function chooseDashboardAction(): Action
    {
        return Action::make('chooseDashboard')
            ->label('Выбрать контекст')
            ->icon('heroicon-o-squares-2x2')
            ->visible(fn (): bool => DashboardWidgetRegistry::canChooseDashboard(auth()->user()))
            ->form([
                Select::make('dashboard_preference')
                    ->label('Рабочий контекст')
                    ->options(fn (): array => DashboardWidgetRegistry::availableContextsFor(auth()->user()))
                    ->default(fn (): string => DashboardWidgetRegistry::contextKeyFor(auth()->user()))
                    ->required(),
            ])
            ->action(function (array $data): void {
                auth()->user()?->update([
                    'dashboard_preference' => $data['dashboard_preference'],
                ]);

                Notification::make()
                    ->title('Рабочий контекст обновлён.')
                    ->success()
                    ->send();

                $this->redirect(static::getUrl());
            });
    }

    public function getContextKey(): string
    {
        return DashboardWidgetRegistry::contextKeyFor(auth()->user());
    }

    public function getContextLabel(): string
    {
        return DashboardWidgetRegistry::contextLabelFor(auth()->user());
    }

    public function getGreeting(): string
    {
        $hour = (int) now()->format('H');

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Доброе утро',
            $hour >= 12 && $hour < 16 => 'Добрый день',
            $hour >= 16 && $hour < 23 => 'Добрый вечер',
            default => 'Доброй ночи',
        };
    }

    private function currentLocalDate(): Carbon
    {
        $date = request()->cookie('niktrade_local_date');

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            try {
                return Carbon::parse($date)->startOfDay();
            } catch (\Throwable) {
                // Fall back to the server date if the browser cookie is malformed.
            }
        }

        return today();
    }

    protected function getViewData(): array
    {
        return [
            'employeeWorkspace' => $this->getContextKey() === DashboardWidgetRegistry::CONTEXT_EMPLOYEE
                ? $this->employeeWorkspaceData()
                : [],
            'hrWorkspace' => $this->getContextKey() === DashboardWidgetRegistry::CONTEXT_HR
                ? $this->hrWorkspaceData()
                : [],
        ];
    }

    private function hrWorkspaceData(): array
    {
        $today = $this->currentLocalDate();
        $weekEnd = $today->copy()->addDays(7);

        $activeRequestStatuses = [
            EmployeeScheduleRequest::STATUS_PENDING,
            EmployeeScheduleRequest::STATUS_IN_REVIEW,
            EmployeeScheduleRequest::STATUS_FORWARDED,
        ];

        $todayEntries = EmployeeScheduleEntry::query()
            ->active()
            ->hrVisible()
            ->with('employee.department')
            ->whereDate('date', $today)
            ->get();

        $employeesCount = User::query()
            ->whereNull('archived_at')
            ->count();

        $newEmployeesCount = User::query()
            ->whereNull('archived_at')
            ->whereDate('created_at', '>=', $today->copy()->startOfMonth())
            ->count();

        $pendingRequestsCount = EmployeeScheduleRequest::query()
            ->whereNull('deleted_at')
            ->whereIn('status', $activeRequestStatuses)
            ->count();

        $expiringDocumentsCount = EmployeeDocument::query()
            ->whereNull('archived_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$today->toDateString(), $today->copy()->addDays(30)->toDateString()])
            ->count();

        $requests = EmployeeScheduleRequest::query()
            ->with('employee')
            ->whereNull('deleted_at')
            ->whereIn('status', $activeRequestStatuses)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (EmployeeScheduleRequest $request): array => [
                'employee' => $request->employee?->name ?? 'Сотрудник',
                'type' => $request->getTypeLabel(),
                'period' => $request->getDateRangeLabel(),
                'status' => $request->getStatusLabel(),
                'url' => EmployeeScheduleRequestResource::getUrl('index'),
            ])
            ->all();

        $upcomingEvents = EmployeeScheduleEntry::query()
            ->active()
            ->hrVisible()
            ->with('employee')
            ->whereBetween('date', [$today->toDateString(), $weekEnd->toDateString()])
            ->whereIn('type', [EmployeeScheduleEntry::TYPE_VACATION, EmployeeScheduleEntry::TYPE_SICK_LEAVE, EmployeeScheduleEntry::TYPE_DAY_OFF])
            ->orderBy('date')
            ->take(24)
            ->get()
            ->groupBy(fn (EmployeeScheduleEntry $entry): string => implode('|', [
                $entry->employee_id,
                $entry->type,
                $entry->getDisplayTitle(),
                (int) $entry->vacation_without_pay,
                $entry->request_reason_type,
            ]))
            ->flatMap(fn (Collection $entries): Collection => $this->continuousHrPeriods($entries))
            ->sortBy('date')
            ->take(4)
            ->values()
            ->all();

        $birthdays = User::query()
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(fn (User $employee): array => [
                'employee' => $employee->name,
                'date' => $this->nextBirthday($employee, $today),
                'age' => $employee->age_label,
                'url' => UserResource::getUrl('view', ['record' => $employee]),
            ])
            ->filter(fn (array $birthday): bool => $birthday['date']->betweenIncluded($today, $weekEnd))
            ->sortBy('date')
            ->take(3)
            ->values()
            ->all();

        return [
            'employee' => auth()->user(),
            'greeting' => $this->getGreeting(),
            'dateLabel' => $today->translatedFormat('l, d F Y'),
            'localDate' => $today->toDateString(),
            'today' => $today,
            'kpis' => [
                ['label' => 'Сотрудники', 'value' => $employeesCount, 'delta' => '+'.$newEmployeesCount, 'tone' => 'blue', 'icon' => 'users'],
                ['label' => 'Новые', 'value' => $newEmployeesCount, 'delta' => '+'.$newEmployeesCount, 'tone' => 'cyan', 'icon' => 'plus'],
                ['label' => 'Отпуска', 'value' => $todayEntries->where('type', EmployeeScheduleEntry::TYPE_VACATION)->count(), 'delta' => '+0', 'tone' => 'amber', 'icon' => 'calendar'],
                ['label' => 'Больничные', 'value' => $todayEntries->where('type', EmployeeScheduleEntry::TYPE_SICK_LEAVE)->count(), 'delta' => '-0', 'tone' => 'red', 'icon' => 'alert'],
                ['label' => 'Заявки', 'value' => $pendingRequestsCount, 'delta' => '+0', 'tone' => 'violet', 'icon' => 'link'],
                ['label' => 'Документы', 'value' => $expiringDocumentsCount, 'delta' => '!', 'tone' => 'rose', 'icon' => 'file'],
            ],
            'requests' => $requests,
            'upcomingEvents' => $upcomingEvents,
            'birthdays' => $birthdays,
            'urls' => [
                'employees' => UserResource::getUrl('index'),
                'createEmployee' => UserResource::getUrl('create'),
                'calendar' => MyCalendar::getUrl(),
                'requests' => EmployeeScheduleRequestResource::getUrl('index'),
                'createRequest' => EmployeeScheduleRequestResource::getUrl('create'),
                'departments' => \App\Filament\Resources\Departments\DepartmentResource::getUrl('index'),
            ],
        ];
    }

    private function continuousHrPeriods(Collection $entries): Collection
    {
        return $entries
            ->sortBy('date')
            ->values()
            ->reduce(function (Collection $periods, EmployeeScheduleEntry $entry): Collection {
                $date = $entry->date->copy()->startOfDay();
                $lastIndex = $periods->count() - 1;
                $last = $lastIndex >= 0 ? $periods->get($lastIndex) : null;

                if ($last && $last['endDate']->copy()->addDay()->isSameDay($date)) {
                    $last['endDate'] = $date;
                    $periods->put($lastIndex, $last);

                    return $periods;
                }

                $periods->push([
                    'date' => $date,
                    'endDate' => $date,
                    'label' => $entry->getDisplayTitle(),
                    'employee' => $entry->employee?->name ?? 'Сотрудник',
                    'url' => $entry->employee ? UserResource::getUrl('view', ['record' => $entry->employee]) : null,
                    'tone' => match ($entry->type) {
                        EmployeeScheduleEntry::TYPE_VACATION => 'violet',
                        EmployeeScheduleEntry::TYPE_SICK_LEAVE => 'red',
                        default => 'amber',
                    },
                ]);

                return $periods;
            }, collect());
    }

    private function nextBirthday(User $employee, ?Carbon $today = null): Carbon
    {
        $today ??= $this->currentLocalDate();
        $birthday = Carbon::parse($employee->date_of_birth)->year((int) $today->format('Y'));

        return $birthday->isBefore($today) ? $birthday->addYear() : $birthday;
    }

    private function employeeWorkspaceData(): array
    {
        /** @var User|null $employee */
        $employee = auth()->user();

        if (! $employee instanceof User) {
            return [];
        }

        $today = $this->currentLocalDate();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $workday = EmployeeWorkday::for($employee, $today);

        $todayEntries = $employee->scheduleEntries()
            ->active()
            ->whereDate('date', $today)
            ->orderBy('starts_at')
            ->get();

        $monthEntries = $employee->scheduleEntries()
            ->active()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy(fn (EmployeeScheduleEntry $entry): string => $entry->date->toDateString());

        $requests = $employee->scheduleRequests()
            ->whereNull('deleted_at')
            ->latest()
            ->take(3)
            ->get();

        $activeDocuments = $employee->activeDocuments()
            ->latest()
            ->take(3)
            ->get();

        $expiringDocuments = $employee->expiringDocuments();

        $todayStatus = $this->employeeStatusLabel($todayEntries, $workday);
        $dayMessage = $this->employeeDayMessage($workday, $todayStatus);

        return [
            'employee' => $employee,
            'greeting' => $this->getGreeting(),
            'dateLabel' => $today->translatedFormat('l, d F Y'),
            'localDate' => $today->toDateString(),
            'monthLabel' => $monthStart->translatedFormat('F Y'),
            'todayStatus' => $todayStatus,
            'dayMessage' => $dayMessage,
            'dayMessageMode' => $this->employeeDayMessageMode($workday, $todayStatus),
            'shiftLabel' => $this->shiftLabel($todayEntries, $workday),
            'hoursToday' => $this->plannedHours($todayEntries, $workday),
            'hoursPlanLabel' => $this->hoursLabel($this->plannedHours($todayEntries, $workday)),
            'progressPercent' => $this->progressPercent($todayEntries, $workday),
            'workday' => $workday,
            'timeline' => $this->timeline($todayEntries, $workday),
            'calendarDays' => $this->calendarDays($monthStart, $monthEnd, $monthEntries, $today),
            'attentionItems' => $this->attentionItems($employee, $expiringDocuments, $today),
            'requestCounts' => $this->requestCounts($employee),
            'recentRequests' => $requests,
            'documents' => [
                'completeness' => $employee->documentCompletenessPercent(),
                'missingCount' => count($employee->missingRequiredDocuments()),
                'expiringCount' => $expiringDocuments->filter(fn (EmployeeDocument $document): bool => ! $document->isExpired())->count(),
                'expiredCount' => $expiringDocuments->filter(fn (EmployeeDocument $document): bool => $document->isExpired())->count(),
                'items' => $activeDocuments,
            ],
            'urls' => [
                'calendar' => MyCalendar::getUrl(),
                'requests' => EmployeeScheduleRequestResource::getUrl('index'),
                'createRequest' => EmployeeScheduleRequestResource::getUrl('create'),
                'documents' => '#employee-documents',
            ],
        ];
    }

    private function employeeStatusLabel(Collection $entries, array $workday): string
    {
        if ($entries->where('type', EmployeeScheduleEntry::TYPE_VACATION)->isNotEmpty()) {
            return 'Отпуск';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_SICK_LEAVE)->isNotEmpty()) {
            return 'Больничный';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_DAY_OFF)->isNotEmpty()) {
            return $entries
                ->where('type', EmployeeScheduleEntry::TYPE_DAY_OFF)
                ->where('request_reason_type', EmployeeScheduleEntry::REASON_TIME_OFF)
                ->isNotEmpty()
                    ? 'Отгул'
                    : 'Выходной';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_TRAINING)->isNotEmpty()) {
            return 'Обучение';
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_MEDICAL_EXAM)->isNotEmpty()) {
            return 'Медосмотр';
        }

        if ($workday['isConfiguredSchedule']) {
            return $workday['statusLabel'];
        }

        if ($entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT)->isNotEmpty()) {
            return 'Рабочий день';
        }

        return 'Смен не назначено';
    }

    private function employeeDayMessage(array $workday, string $todayStatus): string
    {
        if ($todayStatus === 'Отпуск') {
            return 'Хорошего отпуска!';
        }

        if ($todayStatus === 'Больничный') {
            return 'Выздоравливайте!';
        }

        if ($todayStatus === 'Отгул') {
            return 'Хорошего отдыха!';
        }

        if ($todayStatus === 'Обучение') {
            return 'Продуктивного обучения!';
        }

        if ($todayStatus === 'Медосмотр') {
            return 'Удачного медосмотра!';
        }

        if ($workday['isHoliday']) {
            return 'С праздником!';
        }

        if ($todayStatus === 'Выходной') {
            return 'Удачных выходных!';
        }

        return 'Хорошего отдыха!';
    }

    private function employeeDayMessageMode(array $workday, string $todayStatus): string
    {
        if (in_array($todayStatus, [
            'Отпуск',
            'Больничный',
            'Отгул',
            'Выходной',
            'Обучение',
            'Медосмотр',
        ], true) || $workday['isHoliday']) {
            return 'always';
        }

        return 'after_workday';
    }

    private function shiftLabel(Collection $entries, array $workday): ?string
    {
        $shifts = $entries->where('type', EmployeeScheduleEntry::TYPE_SHIFT);

        if ($workday['isConfiguredSchedule']) {
            return $workday['isWorkday']
                ? $workday['startsAt'].'-'.$workday['endsAt']
                : $workday['statusLabel'];
        }

        return $shifts->isNotEmpty()
            ? $shifts->map(fn (EmployeeScheduleEntry $entry): string => $entry->timeLabel())->join(', ')
            : null;
    }

    private function plannedHours(Collection $entries, array $workday): float
    {
        if ($this->hasFullDayAbsence($entries)) {
            return 0;
        }

        if ($workday['isConfiguredSchedule']) {
            return round($workday['plannedMinutes'] / 60, 1);
        }

        return round($entries
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

    private function progressPercent(Collection $entries, array $workday): int
    {
        if ($this->hasFullDayAbsence($entries)) {
            return 0;
        }

        if ($workday['isConfiguredSchedule']) {
            return 0;
        }

        return (int) min(100, ($this->plannedHours($entries, $workday) / 8) * 100);
    }

    private function hoursLabel(float $hours): string
    {
        return number_format($hours, 1, ',', ' ').' ч';
    }

    private function hasFullDayAbsence(Collection $entries): bool
    {
        return $entries
            ->whereIn('type', [
                EmployeeScheduleEntry::TYPE_VACATION,
                EmployeeScheduleEntry::TYPE_SICK_LEAVE,
                EmployeeScheduleEntry::TYPE_DAY_OFF,
            ])
            ->isNotEmpty();
    }

    private function timeline(Collection $entries, array $workday): array
    {
        $items = [];

        if ($workday['isConfiguredSchedule'] && ! $this->hasFullDayAbsence($entries)) {
            if (! $workday['isWorkday']) {
                $items[] = [
                    'time' => 'Весь день',
                    'title' => $workday['statusLabel'],
                    'meta' => $workday['note'] ?: 'По графику 5/2',
                    'color' => $workday['isHoliday'] ? '#ef4444' : '#94a3b8',
                ];
            } else {
                $items[] = ['time' => $workday['startsAt'], 'title' => 'Начало рабочего дня', 'meta' => 'График 5/2', 'color' => '#94a3b8', 'workdayMarker' => 'start'];
                $items[] = ['time' => $workday['lunchStartsAt'], 'title' => 'Обеденный перерыв', 'meta' => $workday['lunchStartsAt'].'-'.$workday['lunchEndsAt'], 'color' => '#94a3b8', 'workdayMarker' => 'lunch'];
                $items[] = ['time' => $workday['endsAt'], 'title' => $workday['isShortened'] ? 'Сокращённый день' : 'Конец рабочего дня', 'meta' => $workday['note'] ?: 'Хорошего вечера', 'color' => '#94a3b8', 'workdayMarker' => 'end'];
            }
        }

        foreach ($entries as $entry) {
            if ($workday['isConfiguredSchedule'] && $entry->type === EmployeeScheduleEntry::TYPE_SHIFT) {
                continue;
            }

            if ($entry->type === EmployeeScheduleEntry::TYPE_SHIFT && $entry->starts_at && $entry->ends_at) {
                $items[] = ['time' => substr((string) $entry->starts_at, 0, 5), 'title' => 'Начало смены', 'meta' => 'Рабочий день', 'color' => '#22c55e'];

                if (substr((string) $entry->starts_at, 0, 5) <= '13:00' && substr((string) $entry->ends_at, 0, 5) >= '14:00') {
                    $items[] = ['time' => '13:00', 'title' => 'Обеденный перерыв', 'meta' => '13:00 — 14:00', 'color' => '#94a3b8'];
                }

                $items[] = ['time' => substr((string) $entry->ends_at, 0, 5), 'title' => 'Конец смены', 'meta' => 'Хорошего вечера', 'color' => '#94a3b8'];

                continue;
            }

            $items[] = [
                'time' => $entry->is_all_day ? 'Весь день' : $entry->timeLabel(),
                'title' => $entry->getDisplayTitle(),
                'meta' => $entry->comment ?: 'Событие календаря',
                'color' => $entry->getCalendarColor(),
            ];
        }

        return collect($items)
            ->sortBy('time')
            ->values()
            ->all();
    }

    private function calendarDays($monthStart, $monthEnd, Collection $entries, Carbon $today): array
    {
        $days = [];
        $cursor = $monthStart->copy()->startOfWeek();
        $last = $monthEnd->copy()->endOfWeek();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $monthStart->month,
                'isToday' => $cursor->isSameDay($today),
                'events' => $entries->get($key, collect())->take(4)->values(),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    private function attentionItems(User $employee, Collection $expiringDocuments, Carbon $today): array
    {
        $items = collect();

        $expiringDocuments
            ->take(2)
            ->each(fn (EmployeeDocument $document) => $items->push([
                'tone' => $document->isExpired() ? 'red' : 'amber',
                'title' => $document->isExpired() ? 'Документ просрочен' : 'Документ скоро истекает',
                'text' => $document->getCategoryLabel(),
            ]));

        $returnedRequests = $employee->scheduleRequests()
            ->where('status', EmployeeScheduleRequest::STATUS_RETURNED)
            ->whereNull('deleted_at')
            ->count();

        if ($returnedRequests > 0) {
            $items->push([
                'tone' => 'red',
                'title' => 'Заявка возвращена',
                'text' => "Возвращено заявок: {$returnedRequests}",
            ]);
        }

        if ($employee->probation_enabled && $employee->probation_ends_at?->betweenIncluded($today, $today->copy()->addDays(14))) {
            $items->push([
                'tone' => 'blue',
                'title' => 'Испытательный срок',
                'text' => 'Заканчивается '.$employee->probation_ends_at->format('d.m.Y'),
            ]);
        }

        return $items->take(3)->values()->all();
    }

    private function requestCounts(User $employee): array
    {
        return [
            'pending' => $employee->scheduleRequests()
                ->whereIn('status', [
                    EmployeeScheduleRequest::STATUS_PENDING,
                    EmployeeScheduleRequest::STATUS_IN_REVIEW,
                    EmployeeScheduleRequest::STATUS_FORWARDED,
                ])
                ->whereNull('deleted_at')
                ->count(),
            'approved' => $employee->scheduleRequests()
                ->where('status', EmployeeScheduleRequest::STATUS_APPROVED)
                ->whereNull('deleted_at')
                ->count(),
            'rejected' => $employee->scheduleRequests()
                ->where('status', EmployeeScheduleRequest::STATUS_REJECTED)
                ->whereNull('deleted_at')
                ->count(),
            'returned' => $employee->scheduleRequests()
                ->where('status', EmployeeScheduleRequest::STATUS_RETURNED)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}
