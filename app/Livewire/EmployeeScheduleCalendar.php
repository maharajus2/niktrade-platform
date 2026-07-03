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

    public string $month;

    public bool $entryFormVisible = false;

    public ?int $editingEntryId = null;

    public string $entryDate = '';

    public string $startsAt = '09:00';

    public string $endsAt = '18:00';

    public ?string $comment = null;

    public bool $generateFormVisible = false;

    public string $generateFromDate = '';

    public string $generateToDate = '';

    /** @var array<int, int|string> */
    public array $generateWeekdays = [1, 2, 3, 4, 5];

    public string $generateStartsAt = '09:00';

    public string $generateEndsAt = '18:00';

    public ?string $generateComment = null;

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;
        $this->month = today()->startOfMonth()->toDateString();
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::parse($this->month)->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->resetOpenForms();
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month)->addMonthNoOverflow()->startOfMonth()->toDateString();
        $this->resetOpenForms();
    }

    public function goToToday(): void
    {
        $this->month = today()->startOfMonth()->toDateString();
        $this->resetOpenForms();
    }

    public function startCreate(string $date): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        if (! $this->employee()->isIndividualSchedule()) {
            $this->notifyWarning('Календарь доступен только для индивидуального графика.');

            return;
        }

        if (Carbon::parse($date)->startOfDay()->lt(today())) {
            $this->notifyWarning('Нельзя изменять прошедшие смены.');

            return;
        }

        $this->entryFormVisible = true;
        $this->generateFormVisible = false;
        $this->editingEntryId = null;
        $this->entryDate = Carbon::parse($date)->toDateString();
        $this->startsAt = '09:00';
        $this->endsAt = '18:00';
        $this->comment = null;
    }

    public function startEdit(int $entryId): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        $entry = $this->findEmployeeEntry($entryId);

        if ($entry->date->startOfDay()->lt(today())) {
            $this->notifyWarning('Нельзя изменять прошедшие смены.');

            return;
        }

        $this->entryFormVisible = true;
        $this->generateFormVisible = false;
        $this->editingEntryId = $entry->id;
        $this->entryDate = $entry->date->toDateString();
        $this->startsAt = substr((string) $entry->starts_at, 0, 5);
        $this->endsAt = substr((string) $entry->ends_at, 0, 5);
        $this->comment = $entry->comment;
    }

    public function saveEntry(): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        if (! $this->employee()->isIndividualSchedule()) {
            $this->notifyWarning('Календарь доступен только для индивидуального графика.');

            return;
        }

        $data = $this->validateEntryForm();
        $date = Carbon::parse($data['entryDate'])->toDateString();

        if (Carbon::parse($date)->startOfDay()->lt(today())) {
            $this->notifyWarning('Нельзя изменять прошедшие смены.');

            return;
        }

        if ($this->hasDuplicateEntry($date, $data['startsAt'], $data['endsAt'], $this->editingEntryId)) {
            throw ValidationException::withMessages([
                'startsAt' => 'Такая смена уже есть в графике сотрудника.',
            ]);
        }

        $payload = [
            'date' => $date,
            'starts_at' => $data['startsAt'],
            'ends_at' => $data['endsAt'],
            'comment' => $data['comment'],
            'updated_by' => auth()->id(),
        ];

        if ($this->editingEntryId) {
            $entry = $this->findEmployeeEntry($this->editingEntryId);

            if ($entry->date->startOfDay()->lt(today())) {
                $this->notifyWarning('Нельзя изменять прошедшие смены.');

                return;
            }

            $entry->update($payload);
        } else {
            $this->employee()->scheduleEntries()->create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        $this->resetEntryForm();

        Notification::make()
            ->title('Смена сохранена.')
            ->success()
            ->send();
    }

    public function deleteEntry(int $entryId): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        $entry = $this->findEmployeeEntry($entryId);

        if ($entry->date->startOfDay()->lt(today())) {
            $this->notifyWarning('Нельзя изменять прошедшие смены.');

            return;
        }

        $entry->delete();

        if ($this->editingEntryId === $entryId) {
            $this->resetEntryForm();
        }

        Notification::make()
            ->title('Смена удалена.')
            ->success()
            ->send();
    }

    public function cancelEntryForm(): void
    {
        $this->resetEntryForm();
    }

    public function openGenerateForm(): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        if (! $this->employee()->isIndividualSchedule()) {
            $this->notifyWarning('Календарь доступен только для индивидуального графика.');

            return;
        }

        $month = Carbon::parse($this->month)->startOfMonth();

        if ($month->copy()->endOfMonth()->lt(today())) {
            $this->notifyWarning('Нельзя изменять прошедшие смены.');

            return;
        }

        $this->generateFormVisible = true;
        $this->entryFormVisible = false;
        $this->generateFromDate = max($month->toDateString(), today()->toDateString());
        $this->generateToDate = $month->copy()->endOfMonth()->toDateString();
        $this->generateWeekdays = [1, 2, 3, 4, 5];
        $this->generateStartsAt = '09:00';
        $this->generateEndsAt = '18:00';
        $this->generateComment = null;
    }

    public function generateMonth(): void
    {
        if (! $this->canUpdateSchedule()) {
            $this->denyScheduleAction();

            return;
        }

        if (! $this->employee()->isIndividualSchedule()) {
            $this->notifyWarning('Календарь доступен только для индивидуального графика.');

            return;
        }

        $data = $this->validate([
            'generateFromDate' => ['required', 'date', 'after_or_equal:today'],
            'generateToDate' => ['required', 'date', 'after_or_equal:generateFromDate'],
            'generateWeekdays' => ['required', 'array', 'min:1'],
            'generateWeekdays.*' => ['integer', 'between:1,7'],
            'generateStartsAt' => ['required', 'date_format:H:i'],
            'generateEndsAt' => ['required', 'date_format:H:i', 'after:generateStartsAt'],
            'generateComment' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'generateFromDate' => 'дата начала',
            'generateToDate' => 'дата окончания',
            'generateWeekdays' => 'дни недели',
            'generateStartsAt' => 'начало',
            'generateEndsAt' => 'окончание',
            'generateComment' => 'комментарий',
        ]);

        $employee = $this->employee();
        $from = Carbon::parse($data['generateFromDate'])->startOfDay();
        $to = Carbon::parse($data['generateToDate'])->startOfDay();
        $weekdays = array_map('intval', $data['generateWeekdays']);
        $created = 0;

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            if (! in_array((int) $date->isoWeekday(), $weekdays, true)) {
                continue;
            }

            $dateString = $date->toDateString();

            if ($this->hasDuplicateEntry($dateString, $data['generateStartsAt'], $data['generateEndsAt'])) {
                continue;
            }

            $employee->scheduleEntries()->create([
                'date' => $dateString,
                'starts_at' => $data['generateStartsAt'],
                'ends_at' => $data['generateEndsAt'],
                'comment' => $data['generateComment'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $created++;
        }

        $this->generateFormVisible = false;

        Notification::make()
            ->title("Создано смен: {$created}.")
            ->success()
            ->send();
    }

    public function cancelGenerateForm(): void
    {
        $this->generateFormVisible = false;
    }

    public function render(): View
    {
        $employee = $this->employee();
        $canUpdate = $this->canUpdateSchedule();
        $monthStart = Carbon::parse($this->month)->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $entriesByDate = $employee->scheduleEntries()
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (EmployeeScheduleEntry $entry): string => $entry->date->toDateString());

        $monthEntriesCount = $employee->scheduleEntries()
            ->whereBetween('date', [
                $monthStart->toDateString(),
                $monthStart->copy()->endOfMonth()->toDateString(),
            ])
            ->count();

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->toDateString();

                $week[] = [
                    'date' => $date,
                    'day' => $cursor->day,
                    'inMonth' => $cursor->isSameMonth($monthStart),
                    'isToday' => $cursor->isToday(),
                    'isPast' => $cursor->lt(today()),
                    'entries' => $entriesByDate->get($date, collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return view('livewire.employee-schedule-calendar', [
            'employee' => $employee,
            'canUpdate' => $canUpdate,
            'canGenerate' => $canUpdate && $monthStart->copy()->endOfMonth()->gte(today()),
            'monthLabel' => $this->monthLabel($monthStart),
            'monthEntriesCount' => $monthEntriesCount,
            'weeks' => $weeks,
            'weekdays' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
        ]);
    }

    private function employee(): User
    {
        return User::query()->findOrFail($this->employeeId);
    }

    private function canUpdateSchedule(): bool
    {
        return UserResource::canUpdateEmployeeSchedule($this->employee());
    }

    private function findEmployeeEntry(int $entryId): EmployeeScheduleEntry
    {
        return EmployeeScheduleEntry::query()
            ->where('employee_id', $this->employeeId)
            ->findOrFail($entryId);
    }

    private function hasDuplicateEntry(string $date, string $startsAt, string $endsAt, ?int $exceptId = null): bool
    {
        return EmployeeScheduleEntry::query()
            ->where('employee_id', $this->employeeId)
            ->whereDate('date', $date)
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    /**
     * @return array{entryDate: string, startsAt: string, endsAt: string, comment: ?string}
     */
    private function validateEntryForm(): array
    {
        return $this->validate([
            'entryDate' => ['required', 'date', 'after_or_equal:today'],
            'startsAt' => ['required', 'date_format:H:i'],
            'endsAt' => ['required', 'date_format:H:i', 'after:startsAt'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'entryDate' => 'дата',
            'startsAt' => 'начало',
            'endsAt' => 'окончание',
            'comment' => 'комментарий',
        ]);
    }

    private function resetOpenForms(): void
    {
        $this->resetEntryForm();
        $this->generateFormVisible = false;
    }

    private function resetEntryForm(): void
    {
        $this->entryFormVisible = false;
        $this->editingEntryId = null;
        $this->entryDate = '';
        $this->startsAt = '09:00';
        $this->endsAt = '18:00';
        $this->comment = null;
    }

    private function denyScheduleAction(): void
    {
        $this->notifyWarning('Недостаточно прав для изменения графика.');
    }

    private function notifyWarning(string $title): void
    {
        Notification::make()
            ->title($title)
            ->warning()
            ->send();
    }

    private function monthLabel(Carbon $month): string
    {
        $months = [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ];

        return ($months[$month->month] ?? $month->translatedFormat('F')).' '.$month->year;
    }
}
