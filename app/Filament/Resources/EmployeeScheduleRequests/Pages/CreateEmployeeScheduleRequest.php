<?php

namespace App\Filament\Resources\EmployeeScheduleRequests\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeScheduleRequest;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateEmployeeScheduleRequest extends CreateRecord
{
    protected static string $resource = EmployeeScheduleRequestResource::class;

    protected static ?string $title = 'Создать заявку';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! array_key_exists((int) ($data['employee_id'] ?? 0), EmployeeScheduleRequestResource::employeeOptionsForCurrentUser())) {
            throw ValidationException::withMessages([
                'data.employee_id' => 'Выберите доступного сотрудника.',
            ]);
        }

        $this->validateRequestData($data);

        $type = $data['type'];

        if (in_array($type, [
            EmployeeScheduleRequest::TYPE_DAY_OFF,
            EmployeeScheduleRequest::TYPE_VACATION,
            EmployeeScheduleRequest::TYPE_SICK_LEAVE,
        ], true)) {
            $data['is_all_day'] = true;
            $data['starts_at'] = null;
            $data['ends_at'] = null;
            $data['title'] = null;
        }

        if ($type === EmployeeScheduleRequest::TYPE_SHIFT) {
            $data['is_all_day'] = false;
            $data['title'] = null;
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

    private function validateRequestData(array $data): void
    {
        $startDate = Carbon::parse($data['start_date'] ?? null)->toDateString();
        $endDate = Carbon::parse($data['end_date'] ?? null)->toDateString();
        $type = (string) ($data['type'] ?? '');
        $isAllDay = (bool) ($data['is_all_day'] ?? false);
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
}
