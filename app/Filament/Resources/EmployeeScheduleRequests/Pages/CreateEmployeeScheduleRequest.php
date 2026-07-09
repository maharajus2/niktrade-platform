<?php

namespace App\Filament\Resources\EmployeeScheduleRequests\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use App\Services\Approvals\ApprovalWorkflowService;
use Carbon\Carbon;
use DateTimeInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CreateEmployeeScheduleRequest extends CreateRecord
{
    protected static string $resource = EmployeeScheduleRequestResource::class;

    protected string $view = 'filament.resources.employee-schedule-requests.pages.create-request';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Создать заявку';

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

    protected function beforeCreate(): void
    {
        if (
            Schema::hasTable('employee_schedule_requests')
            && Schema::hasTable('approval_workflows')
            && Schema::hasTable('approval_workflow_events')
            && Schema::hasColumn('employee_schedule_requests', 'request_reason_type')
            && Schema::hasColumn('employee_schedule_requests', 'vacation_without_pay')
        ) {
            return;
        }

        Notification::make()
            ->title('Заявку нельзя сохранить')
            ->body('Миграции для заявок на график ещё не применены. Примените миграции и повторите попытку.')
            ->danger()
            ->send();

        throw (new Halt())->rollBackDatabaseTransaction();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! array_key_exists((int) ($data['employee_id'] ?? 0), EmployeeScheduleRequestResource::employeeOptionsForCurrentUser())) {
            throw ValidationException::withMessages([
                'data.employee_id' => 'Выберите доступного сотрудника.',
            ]);
        }

        $this->validateRequestData($data);

        $type = $data['type'];

        if ($this->isAlwaysAllDayType($type)) {
            $data['is_all_day'] = true;
            $data['starts_at'] = null;
            $data['ends_at'] = null;
            $data['title'] = null;
        }

        if ($type === EmployeeScheduleRequest::TYPE_DAY_OFF) {
            $data['title'] = null;
            $data['vacation_without_pay'] = false;
        }

        if ($type !== EmployeeScheduleRequest::TYPE_DAY_OFF) {
            $data['request_reason_type'] = null;
        }

        if ($type === EmployeeScheduleRequest::TYPE_SHIFT) {
            $data['is_all_day'] = false;
            $data['title'] = null;
            $data['vacation_without_pay'] = false;
        }

        if ($type !== EmployeeScheduleRequest::TYPE_VACATION) {
            $data['vacation_without_pay'] = false;
        }

        if ((bool) ($data['is_all_day'] ?? false)) {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
        }

        $data['status'] = EmployeeScheduleRequest::STATUS_PENDING;
        $data['requested_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        app(ApprovalWorkflowService::class)->start(
            approvable: $this->record,
            actor: $actor,
            initialApprover: $this->record->employee?->getEffectiveManager(),
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Заявка создана.';
    }

    private function validateRequestData(array $data): void
    {
        $startDate = $this->normalizeDate($data['start_date'] ?? null, 'data.start_date');
        $endDate = $this->normalizeDate($data['end_date'] ?? null, 'data.end_date');
        $type = (string) ($data['type'] ?? '');
        $isAllDay = $this->isAlwaysAllDayType($type) || (bool) ($data['is_all_day'] ?? false);
        $startsAt = filled($data['starts_at'] ?? null) ? substr((string) $data['starts_at'], 0, 5) : null;
        $endsAt = filled($data['ends_at'] ?? null) ? substr((string) $data['ends_at'], 0, 5) : null;

        if ($endDate < $startDate) {
            throw ValidationException::withMessages([
                'data.end_date' => 'Дата окончания должна быть не раньше даты начала.',
            ]);
        }

        if (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1 > 60) {
            throw ValidationException::withMessages([
                'data.end_date' => 'Диапазон не может быть больше 60 дней.',
            ]);
        }

        if ($type === EmployeeScheduleRequest::TYPE_SHIFT && $startDate !== $endDate) {
            throw ValidationException::withMessages([
                'data.end_date' => 'Заявка на смену создаётся только на один день.',
            ]);
        }

        if ($type === EmployeeScheduleRequest::TYPE_CUSTOM && blank($data['title'] ?? null)) {
            throw ValidationException::withMessages([
                'data.title' => 'Укажите название события.',
            ]);
        }

        if ($type === EmployeeScheduleRequest::TYPE_DAY_OFF) {
            $reasonType = (string) ($data['request_reason_type'] ?? '');

            if (! array_key_exists($reasonType, EmployeeScheduleRequest::reasonOptions())) {
                throw ValidationException::withMessages([
                    'data.request_reason_type' => 'Выберите причину.',
                ]);
            }
        }

        if ($type === EmployeeScheduleRequest::TYPE_SHIFT || ! $isAllDay) {
            if (! $startsAt || ! $endsAt) {
                throw ValidationException::withMessages([
                    'data.starts_at' => 'Укажите время начала и окончания.',
                ]);
            }

            if ($startsAt >= $endsAt) {
                throw ValidationException::withMessages([
                    'data.ends_at' => 'Время окончания должно быть позже времени начала.',
                ]);
            }
        }
    }

    private function isAlwaysAllDayType(string $type): bool
    {
        return in_array($type, [
            EmployeeScheduleRequest::TYPE_VACATION,
            EmployeeScheduleRequest::TYPE_SICK_LEAVE,
        ], true);
    }

    private function normalizeDate(mixed $value, string $field): string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (blank($value)) {
            throw ValidationException::withMessages([
                $field => 'Укажите дату.',
            ]);
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Укажите корректную дату.',
            ]);
        }
    }
}
