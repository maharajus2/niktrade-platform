<?php

namespace App\Livewire;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\EmployeeScheduleEntry;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class EmployeeScheduleCalendar extends Component
{
    public int $employeeId;

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarEvents(string $start, string $end): array
    {
        $employee = $this->employee();

        if (! UserResource::canViewEmployeeSchedule($employee)) {
            return [];
        }

        $startDate = Carbon::parse($start)->toDateString();
        $endDate = Carbon::parse($end)->subDay()->toDateString();

        return $employee->scheduleEntries()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (EmployeeScheduleEntry $entry): array => $this->eventPayload($entry))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCalendarEntry(array $payload): array
    {
        $employee = $this->employee();

        $this->authorizeScheduleUpdate($employee);
        $this->ensureIndividualSchedule($employee);

        $range = $this->normalizeRangePayload($payload);
        $created = 0;
        $skipped = 0;

        for ($date = Carbon::parse($range['start_date']); $date->lte(Carbon::parse($range['end_date'])); $date->addDay()) {
            $data = $range['data'] + [
                'date' => $date->toDateString(),
            ];

            $this->ensureEditableDate($data['date']);

            if ($this->hasDuplicate($employee, $data)) {
                $skipped++;

                continue;
            }

            $employee->scheduleEntries()->create($data + [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $created++;
        }

        Notification::make()
            ->title("Создано событий: {$created}. Пропущено дублей: {$skipped}.")
            ->success()
            ->send();

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateCalendarEntry(int $entryId, array $payload): array
    {
        $employee = $this->employee();
        $entry = $this->findEmployeeEntry($entryId);

        $this->authorizeScheduleUpdate($employee);
        $this->ensureIndividualSchedule($employee);
        $this->ensureEditableDate($entry->date->toDateString());

        $data = $this->normalizeSinglePayload($payload);
        $this->ensureEditableDate($data['date']);
        $this->ensureNoDuplicate($employee, $data, $entry->id);

        $entry->update($data + [
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Событие сохранено.')
            ->success()
            ->send();

        return $this->eventPayload($entry->refresh());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function moveCalendarEntry(int $entryId, array $payload): array
    {
        $entry = $this->findEmployeeEntry($entryId);

        return $this->updateCalendarEntry($entryId, [
            'type' => $entry->type,
            'title' => $entry->title,
            'start_date' => $payload['date'] ?? $entry->date->toDateString(),
            'end_date' => $payload['date'] ?? $entry->date->toDateString(),
            'is_all_day' => $payload['is_all_day'] ?? $entry->is_all_day,
            'starts_at' => $payload['starts_at'] ?? $entry->starts_at,
            'ends_at' => $payload['ends_at'] ?? $entry->ends_at,
            'comment' => $entry->comment,
        ]);
    }

    public function deleteCalendarEntry(int $entryId): void
    {
        $employee = $this->employee();
        $entry = $this->findEmployeeEntry($entryId);

        $this->authorizeScheduleUpdate($employee);
        $this->ensureEditableDate($entry->date->toDateString());

        $entry->delete();

        Notification::make()
            ->title('Событие удалено.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public function getTypeOptions(): array
    {
        return EmployeeScheduleEntry::typeOptions();
    }

    public function render(): View
    {
        $employee = $this->employee();
        $canUpdate = UserResource::canUpdateEmployeeSchedule($employee);
        $user = auth()->user();

        return view('livewire.employee-schedule-calendar', [
            'employee' => $employee,
            'canUpdate' => $canUpdate,
            'canEditPast' => $user instanceof User && $user->hasRole('super_admin'),
        ]);
    }

    private function employee(): User
    {
        return User::query()->findOrFail($this->employeeId);
    }

    private function findEmployeeEntry(int $entryId): EmployeeScheduleEntry
    {
        return EmployeeScheduleEntry::query()
            ->where('employee_id', $this->employeeId)
            ->findOrFail($entryId);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(EmployeeScheduleEntry $entry): array
    {
        $date = $entry->date->toDateString();
        $startsAt = $this->trimNullableTime($entry->starts_at);
        $endsAt = $this->trimNullableTime($entry->ends_at);
        $canUpdate = UserResource::canUpdateEmployeeSchedule($entry->employee);
        $isEditable = $canUpdate && $this->canModifyDate($date);
        $title = $entry->getDisplayTitle();

        if ($entry->comment) {
            $title .= ' · '.$entry->comment;
        }

        return [
            'id' => (string) $entry->id,
            'title' => $title,
            'start' => $entry->is_all_day || ! $startsAt ? $date : "{$date}T{$startsAt}:00",
            'end' => $entry->is_all_day || ! $endsAt ? null : "{$date}T{$endsAt}:00",
            'allDay' => (bool) $entry->is_all_day,
            'editable' => $isEditable,
            'startEditable' => $isEditable,
            'durationEditable' => $isEditable && ! $entry->is_all_day,
            'backgroundColor' => $entry->getCalendarColor(),
            'borderColor' => $entry->getCalendarColor(),
            'classNames' => array_filter([
                'nt-schedule-event',
                'nt-schedule-event-'.$entry->type,
                $isEditable ? null : 'nt-schedule-event-past',
            ]),
            'extendedProps' => [
                'type' => $entry->type,
                'type_label' => $entry->getTypeLabel(),
                'title' => $entry->title,
                'display_title' => $entry->getDisplayTitle(),
                'date' => $date,
                'is_all_day' => (bool) $entry->is_all_day,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'comment' => $entry->comment,
                'editable' => $isEditable,
                'is_work_time' => $entry->isWorkTime(),
            ],
        ];
    }

    private function authorizeScheduleUpdate(User $employee): void
    {
        if (! UserResource::canUpdateEmployeeSchedule($employee)) {
            throw ValidationException::withMessages([
                'schedule' => 'Недостаточно прав для изменения графика.',
            ]);
        }
    }

    private function ensureIndividualSchedule(User $employee): void
    {
        if (! $employee->isIndividualSchedule()) {
            throw ValidationException::withMessages([
                'schedule' => 'Календарь доступен только для индивидуального графика.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{start_date: string, end_date: string, data: array{type: string, title: ?string, is_all_day: bool, starts_at: ?string, ends_at: ?string, comment: ?string}}
     */
    private function normalizeRangePayload(array $payload): array
    {
        $startDateValue = $payload['start_date'] ?? $payload['date'] ?? null;
        $endDateValue = $payload['end_date'] ?? $payload['date'] ?? $startDateValue;

        if (blank($startDateValue) || blank($endDateValue)) {
            throw ValidationException::withMessages([
                'start_date' => 'Укажите дату начала и дату окончания.',
            ]);
        }

        $startDate = Carbon::parse((string) $startDateValue)->toDateString();
        $endDate = Carbon::parse((string) $endDateValue)->toDateString();

        if ($endDate < $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'Дата окончания должна быть не раньше даты начала.',
            ]);
        }

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        if ($days > 60) {
            throw ValidationException::withMessages([
                'end_date' => 'Диапазон не может быть больше 60 дней.',
            ]);
        }

        $data = $this->normalizeEventPayload($payload);

        if ($data['type'] === EmployeeScheduleEntry::TYPE_SHIFT && $endDate !== $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'Смена создаётся только на один день.',
            ]);
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{type: string, title: ?string, date: string, is_all_day: bool, starts_at: ?string, ends_at: ?string, comment: ?string}
     */
    private function normalizeSinglePayload(array $payload): array
    {
        $range = $this->normalizeRangePayload($payload);

        return $range['data'] + [
            'date' => $range['start_date'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{type: string, title: ?string, is_all_day: bool, starts_at: ?string, ends_at: ?string, comment: ?string}
     */
    private function normalizeEventPayload(array $payload): array
    {
        $type = (string) ($payload['type'] ?? EmployeeScheduleEntry::TYPE_SHIFT);
        $allowedTypes = array_keys(EmployeeScheduleEntry::typeOptions());

        if (! in_array($type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'type' => 'Выберите корректный тип события.',
            ]);
        }

        $isAllDay = filter_var($payload['is_all_day'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (in_array($type, [
            EmployeeScheduleEntry::TYPE_DAY_OFF,
            EmployeeScheduleEntry::TYPE_VACATION,
            EmployeeScheduleEntry::TYPE_SICK_LEAVE,
        ], true)) {
            $isAllDay = true;
        }

        if ($type === EmployeeScheduleEntry::TYPE_SHIFT) {
            $isAllDay = false;
        }

        $title = trim((string) ($payload['title'] ?? ''));

        if ($type === EmployeeScheduleEntry::TYPE_CUSTOM && $title === '') {
            throw ValidationException::withMessages([
                'title' => 'Укажите название события.',
            ]);
        }

        $startsAt = $this->trimNullableTime($payload['starts_at'] ?? null);
        $endsAt = $this->trimNullableTime($payload['ends_at'] ?? null);

        if ($type === EmployeeScheduleEntry::TYPE_SHIFT || ! $isAllDay) {
            if (! $startsAt || ! $endsAt) {
                throw ValidationException::withMessages([
                    'time' => 'Укажите время начала и окончания.',
                ]);
            }

            $this->ensureValidTimeRange($startsAt, $endsAt);
        } else {
            $startsAt = null;
            $endsAt = null;
        }

        return [
            'type' => $type,
            'title' => $title === '' ? null : $title,
            'is_all_day' => $isAllDay,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'comment' => blank($payload['comment'] ?? null) ? null : trim((string) $payload['comment']),
        ];
    }

    private function trimNullableTime(mixed $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr((string) $time, 0, 5);
    }

    private function ensureValidTimeRange(string $startsAt, string $endsAt): void
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $startsAt) || ! preg_match('/^\d{2}:\d{2}$/', $endsAt)) {
            throw ValidationException::withMessages([
                'time' => 'Укажите время в формате ЧЧ:ММ.',
            ]);
        }

        if ($startsAt >= $endsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Время окончания должно быть позже времени начала. Ночные смены через полночь пока оформляются двумя событиями.',
            ]);
        }
    }

    private function ensureEditableDate(string $date): void
    {
        if (! $this->canModifyDate($date)) {
            throw ValidationException::withMessages([
                'date' => 'Нельзя изменять прошедшие события.',
            ]);
        }
    }

    private function canModifyDate(string $date): bool
    {
        $user = auth()->user();

        return ($user instanceof User && $user->hasRole('super_admin'))
            || ! Carbon::parse($date)->startOfDay()->lt(today());
    }

    /**
     * @param  array{type: string, date: string, is_all_day: bool, starts_at: ?string, ends_at: ?string, title: ?string}  $data
     */
    private function ensureNoDuplicate(User $employee, array $data, ?int $exceptId = null): void
    {
        if ($this->hasDuplicate($employee, $data, $exceptId)) {
            throw ValidationException::withMessages([
                'schedule' => 'Такое событие уже есть в графике сотрудника.',
            ]);
        }
    }

    /**
     * @param  array{type: string, date: string, is_all_day: bool, starts_at: ?string, ends_at: ?string, title: ?string}  $data
     */
    private function hasDuplicate(User $employee, array $data, ?int $exceptId = null): bool
    {
        return $employee->scheduleEntries()
            ->whereDate('date', $data['date'])
            ->where('type', $data['type'])
            ->where('title', $data['title'])
            ->where('is_all_day', $data['is_all_day'])
            ->where('starts_at', $data['starts_at'])
            ->where('ends_at', $data['ends_at'])
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}
