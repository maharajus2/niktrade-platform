<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'type',
    'title',
    'is_all_day',
    'date',
    'starts_at',
    'ends_at',
    'request_reason_type',
    'vacation_without_pay',
    'comment',
    'visibility',
    'source',
    'approved_request_id',
    'created_by',
    'updated_by',
    'archived_at',
])]
class EmployeeScheduleEntry extends Model
{
    public const TYPE_SHIFT = 'shift';

    public const TYPE_DAY_OFF = 'day_off';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_SICK_LEAVE = 'sick_leave';

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_BUSINESS_TRIP = 'business_trip';

    public const TYPE_TRAINING = 'training';

    public const TYPE_PROBATION = 'probation';

    public const TYPE_MEDICAL_EXAM = 'medical_exam';

    public const TYPE_DOCUMENT_REMINDER = 'document_reminder';

    public const TYPE_DOCUMENT_EXPIRATION = self::TYPE_DOCUMENT_REMINDER;

    public const TYPE_WORKFLOW_EVENT = 'workflow_event';

    public const TYPE_EMPLOYMENT_EVENT = self::TYPE_WORKFLOW_EVENT;

    public const TYPE_TASK = 'task';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_PERSONAL = 'personal';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_MANAGER = 'manager';

    public const VISIBILITY_HR = 'hr';

    public const VISIBILITY_DEPARTMENT = 'department';

    public const VISIBILITY_PUBLIC = 'public';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_REQUEST = 'request';

    public const SOURCE_WORKFLOW = 'workflow';

    public const SOURCE_DOCUMENT = 'document';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_INTEGRATION = 'integration';

    public const SOURCE_GEOVISION_FUTURE = 'geovision_future';

    public const SOURCE_TASK_FUTURE = 'task_future';

    public const SOURCE_HR = self::SOURCE_MANUAL;

    public const REASON_TIME_OFF = 'time_off';

    public const REASON_FAMILY = 'family';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SHIFT => 'Смена',
            self::TYPE_DAY_OFF => 'Выходной',
            self::TYPE_VACATION => 'Отпуск',
            self::TYPE_SICK_LEAVE => 'Больничный',
            self::TYPE_BUSINESS_TRIP => 'Командировка',
            self::TYPE_TRAINING => 'Обучение',
            self::TYPE_MEDICAL_EXAM => 'Медосмотр',
            self::TYPE_DOCUMENT_REMINDER => 'Документ',
            self::TYPE_WORKFLOW_EVENT => 'Согласование / заявка',
            self::TYPE_CUSTOM => 'Другое событие',
        ];
    }

    public static function futureTypeOptions(): array
    {
        return [
            self::TYPE_TASK => 'Задача',
            self::TYPE_MEETING => 'Встреча',
            self::TYPE_PERSONAL => 'Личное',
        ];
    }

    public static function reasonOptions(): array
    {
        return [
            self::REASON_TIME_OFF => 'Отгул',
            self::REASON_FAMILY => 'По семейным обстоятельствам',
        ];
    }

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_PRIVATE => 'Личное',
            self::VISIBILITY_MANAGER => 'Сотрудник и руководитель',
            self::VISIBILITY_HR => 'Сотрудник и HR',
            self::VISIBILITY_DEPARTMENT => 'Руководители отдела',
            self::VISIBILITY_PUBLIC => 'Общее',
        ];
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_MANUAL => 'Вручную',
            self::SOURCE_REQUEST => 'Заявка',
            self::SOURCE_WORKFLOW => 'Согласование',
            self::SOURCE_DOCUMENT => 'Документ',
            self::SOURCE_SYSTEM => 'Система',
            self::SOURCE_INTEGRATION => 'Интеграция',
            self::SOURCE_GEOVISION_FUTURE => 'Geovision',
            self::SOURCE_TASK_FUTURE => 'Задачи',
        ];
    }

    public static function hrVisibleTypes(): array
    {
        return [
            self::TYPE_SHIFT,
            self::TYPE_DAY_OFF,
            self::TYPE_VACATION,
            self::TYPE_SICK_LEAVE,
            self::TYPE_BUSINESS_TRIP,
            self::TYPE_TRAINING,
            self::TYPE_MEDICAL_EXAM,
            self::TYPE_DOCUMENT_REMINDER,
            self::TYPE_WORKFLOW_EVENT,
        ];
    }

    public function scopeHrVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('visibility', self::VISIBILITY_HR)
                ->orWhereIn('type', self::hrVisibleTypes());
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isHrVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_HR
            || in_array($this->type, self::hrVisibleTypes(), true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function timeLabel(): string
    {
        if ($this->is_all_day || ! $this->starts_at || ! $this->ends_at) {
            return 'Весь день';
        }

        return substr((string) $this->starts_at, 0, 5).'–'.substr((string) $this->ends_at, 0, 5);
    }

    public function getTypeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? self::typeOptions()[self::TYPE_SHIFT];
    }

    public function getDisplayTitle(): string
    {
        if ($this->type === self::TYPE_SHIFT) {
            return $this->timeLabel().' · '.$this->getTypeLabel();
        }

        if ($this->type === self::TYPE_DAY_OFF && filled($this->request_reason_type)) {
            return self::reasonOptions()[$this->request_reason_type] ?? $this->getTypeLabel();
        }

        if ($this->type === self::TYPE_DAY_OFF && filled($this->title)) {
            return (string) $this->title;
        }

        if ($this->type === self::TYPE_VACATION && $this->vacation_without_pay) {
            return 'Отпуск без сохранения';
        }

        if ($this->type === self::TYPE_CUSTOM && filled($this->title)) {
            return (string) $this->title;
        }

        return $this->getTypeLabel();
    }

    public function getCalendarColor(): string
    {
        return match ($this->type) {
            self::TYPE_DAY_OFF => '#6b7280',
            self::TYPE_VACATION => '#8b5cf6',
            self::TYPE_SICK_LEAVE => '#f97316',
            self::TYPE_BUSINESS_TRIP => '#06b6d4',
            self::TYPE_TRAINING => '#22c55e',
            self::TYPE_MEDICAL_EXAM => '#fb7185',
            self::TYPE_DOCUMENT_REMINDER => '#f59e0b',
            self::TYPE_WORKFLOW_EVENT => '#3b82f6',
            self::TYPE_CUSTOM => '#64748b',
            default => '#1677ff',
        };
    }

    public function isWorkTime(): bool
    {
        return $this->type === self::TYPE_SHIFT
            && ! $this->is_all_day
            && filled($this->starts_at)
            && filled($this->ends_at);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_all_day' => 'boolean',
            'vacation_without_pay' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
