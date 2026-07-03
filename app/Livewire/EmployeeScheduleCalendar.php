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
     * @return array<string, mixed>
     */
    public function createCalendarEntry(string $date, string $startsAt, string $endsAt, ?string $comment = null): array
    {
        $employee = $this->employee();

        $this->authorizeScheduleUpdate($employee);
        $this->ensureIndividualSchedule($employee);

        $date = $this->normalizeDate($date);
        [$startsAt, $endsAt] = $this->normalizeTimeRange($startsAt, $endsAt);

        $this->ensureFutureDate($date);
        $this->ensureNoDuplicate($employee, $date, $startsAt, $endsAt);

        $entry = $employee->scheduleEntries()->create([
            'date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'comment' => $comment ?: null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Смена сохранена.')
            ->success()
            ->send();

        return $this->eventPayload($entry);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateCalendarEntry(int $entryId, string $date, string $startsAt, string $endsAt, ?string $comment = null): array
    {
        $employee = $this->employee();
        $entry = $this->findEmployeeEntry($entryId);

        $this->authorizeScheduleUpdate($employee);
        $this->ensureIndividualSchedule($employee);
        $this->ensureFutureDate($entry->date->toDateString());

        $date = $this->normalizeDate($date);
        [$startsAt, $endsAt] = $this->normalizeTimeRange($startsAt, $endsAt);

        $this->ensureFutureDate($date);
        $this->ensureNoDuplicate($employee, $date, $startsAt, $endsAt, $entry->id);

        $entry->update([
            'date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'comment' => $comment ?: null,
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Смена сохранена.')
            ->success()
            ->send();

        return $this->eventPayload($entry->refresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function moveCalendarEntry(int $entryId, string $date, string $startsAt, string $endsAt): array
    {
        $entry = $this->findEmployeeEntry($entryId);

        return $this->updateCalendarEntry(
            $entryId,
            $date,
            $startsAt,
            $endsAt,
            $entry->comment,
        );
    }

    public function deleteCalendarEntry(int $entryId): void
    {
        $employee = $this->employee();
        $entry = $this->findEmployeeEntry($entryId);

        $this->authorizeScheduleUpdate($employee);
        $this->ensureFutureDate($entry->date->toDateString());

        $entry->delete();

        Notification::make()
            ->title('Смена удалена.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        $employee = $this->employee();
        $canUpdate = UserResource::canUpdateEmployeeSchedule($employee);

        return view('livewire.employee-schedule-calendar', [
            'employee' => $employee,
            'canUpdate' => $canUpdate,
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
        $startsAt = $this->trimTime((string) $entry->starts_at);
        $endsAt = $this->trimTime((string) $entry->ends_at);
        $isFutureEditable = ! Carbon::parse($date)->startOfDay()->lt(today());
        $canUpdate = UserResource::canUpdateEmployeeSchedule($entry->employee);
        $isEditable = $canUpdate && $isFutureEditable;

        return [
            'id' => (string) $entry->id,
            'title' => $entry->timeLabel().($entry->comment ? ' · '.$entry->comment : ''),
            'start' => "{$date}T{$startsAt}:00",
            'end' => "{$date}T{$endsAt}:00",
            'editable' => $isEditable,
            'startEditable' => $isEditable,
            'durationEditable' => $isEditable,
            'classNames' => $isEditable ? ['nt-schedule-event'] : ['nt-schedule-event', 'nt-schedule-event-past'],
            'extendedProps' => [
                'date' => $date,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'comment' => $entry->comment,
                'editable' => $isEditable,
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

    private function normalizeDate(string $date): string
    {
        return Carbon::parse($date)->toDateString();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeTimeRange(string $startsAt, string $endsAt): array
    {
        $startsAt = $this->trimTime($startsAt);
        $endsAt = $this->trimTime($endsAt);

        if (! preg_match('/^\d{2}:\d{2}$/', $startsAt) || ! preg_match('/^\d{2}:\d{2}$/', $endsAt)) {
            throw ValidationException::withMessages([
                'time' => 'Укажите время в формате ЧЧ:ММ.',
            ]);
        }

        if ($startsAt >= $endsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        return [$startsAt, $endsAt];
    }

    private function trimTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function ensureFutureDate(string $date): void
    {
        if (Carbon::parse($date)->startOfDay()->lt(today())) {
            throw ValidationException::withMessages([
                'date' => 'Нельзя изменять прошедшие смены.',
            ]);
        }
    }

    private function ensureNoDuplicate(User $employee, string $date, string $startsAt, string $endsAt, ?int $exceptId = null): void
    {
        $exists = $employee->scheduleEntries()
            ->whereDate('date', $date)
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'schedule' => 'Такая смена уже есть в графике сотрудника.',
            ]);
        }
    }
}
