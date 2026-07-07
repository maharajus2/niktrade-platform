<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleEntry;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use App\Support\Dashboard\DashboardWidgetRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
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
            $hour < 12 => 'Доброе утро',
            $hour < 18 => 'Добрый день',
            default => 'Добрый вечер',
        };
    }

    protected function getViewData(): array
    {
        return [
            'employeeWorkspace' => $this->getContextKey() === DashboardWidgetRegistry::CONTEXT_EMPLOYEE
                ? $this->employeeWorkspaceData()
                : [],
        ];
    }

    private function employeeWorkspaceData(): array
    {
        /** @var User|null $employee */
        $employee = auth()->user();

        if (! $employee instanceof User) {
            return [];
        }

        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $todayEntries = $employee->scheduleEntries()
            ->whereDate('date', $today)
            ->orderBy('starts_at')
            ->get();

        $monthEntries = $employee->scheduleEntries()
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

        return [
            'employee' => $employee,
            'dateLabel' => now()->translatedFormat('l, d F Y'),
            'monthLabel' => $monthStart->translatedFormat('F Y'),
            'todayStatus' => $this->employeeStatusLabel($todayEntries),
            'shiftLabel' => $this->shiftLabel($todayEntries),
            'hoursToday' => $this->plannedHours($todayEntries),
            'timeline' => $this->timeline($todayEntries),
            'calendarDays' => $this->calendarDays($monthStart, $monthEnd, $monthEntries),
            'attentionItems' => $this->attentionItems($employee, $expiringDocuments),
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

    private function plannedHours(Collection $entries): float
    {
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

    private function timeline(Collection $entries): array
    {
        $items = [];

        foreach ($entries as $entry) {
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

        if ($employee->probation_enabled && $employee->probation_ends_at?->betweenIncluded(today(), today()->addDays(14))) {
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
