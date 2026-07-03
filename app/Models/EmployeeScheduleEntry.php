<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    'created_by',
    'updated_by',
])]
class EmployeeScheduleEntry extends Model
{
    public const TYPE_SHIFT = 'shift';

    public const TYPE_DAY_OFF = 'day_off';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_SICK_LEAVE = 'sick_leave';

    public const TYPE_CUSTOM = 'custom';

    public const REASON_TIME_OFF = 'time_off';

    public const REASON_FAMILY = 'family';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SHIFT => 'Смена',
            self::TYPE_DAY_OFF => 'Выходной',
            self::TYPE_VACATION => 'Отпуск',
            self::TYPE_SICK_LEAVE => 'Больничный',
            self::TYPE_CUSTOM => 'Другое событие',
        ];
    }

    public static function reasonOptions(): array
    {
        return [
            self::REASON_TIME_OFF => 'Отгул',
            self::REASON_FAMILY => 'По семейным обстоятельствам',
        ];
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
            self::TYPE_CUSTOM => '#ca8a04',
            default => '#059669',
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
        ];
    }
}
