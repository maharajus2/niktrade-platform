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

        $data = $this->normalizePayload($payload);
        $this->ensureEditableDate($data['date']);
        $this->ensureNoDuplicate($employee, $data);

        $entry = $employee->scheduleEntries()->create($data + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Событие сохранено.')
            ->success()
            ->send();

        return $this->eventPayload($entry);
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

        $data = $this->normalizePayload($payload);
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
            'date' => $payload['date'] ?? $entry->date->toDateString(),
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
     * @return array{type: string, title: ?string, date: string, is_all_day: bool, starts_at: ?string, ends_at: ?string, comment: ?string}
     */
    private function normalizePayload(array $payload): array
    {
        $type = (string) ($payload['type'] ?? EmployeeScheduleEntry::TYPE_SHIFT);
        $allowedTypes = array_keys(EmployeeScheduleEntry::typeOptions());

        if (! in_array($type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'type' => 'Выберите корректный тип события.',
            ]);
        }

        $date = Carbon::parse((string) ($payload['date'] ?? today()->toDateString()))->toDateString();
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
            'date' => $date,
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
        $exists = $employee->scheduleEntries()
            ->whereDate('date', $data['date'])
            ->where('type', $data['type'])
            ->where('is_all_day', $data['is_all_day'])
            ->where('starts_at', $data['starts_at'])
            ->where('ends_at', $data['ends_at'])
            ->when($data['type'] === EmployeeScheduleEntry::TYPE_CUSTOM, fn ($query) => $query->where('title', $data['title']))
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'schedule' => 'Такое событие уже есть в графике сотрудника.',
            ]);
        }
    }
}
