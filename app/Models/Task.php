<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'board_id',
    'column_id',
    'title',
    'description',
    'type',
    'status',
    'priority',
    'creator_id',
    'assignee_id',
    'assigned_by_id',
    'department_id',
    'parent_id',
    'planned_start_at',
    'due_at',
    'sla_minutes',
    'sla_started_at',
    'sla_due_at',
    'completed_at',
    'completed_by',
    'archived_at',
    'archived_by',
    'hold_started_at',
    'hold_reason',
    'hold_by',
])]
class Task extends Model
{
    public const TYPE_PERSONAL = 'personal';

    public const TYPE_MANAGER_ASSIGNED = 'manager_assigned';

    public const TYPE_DELEGATED = 'delegated';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_SYSTEM = 'system';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_REVIEW = 'review';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_ARCHIVED = 'archived';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected function casts(): array
    {
        return [
            'planned_start_at' => 'datetime',
            'due_at' => 'datetime',
            'sla_started_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'hold_started_at' => 'datetime',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Низкий',
            self::PRIORITY_NORMAL => 'Обычный',
            self::PRIORITY_HIGH => 'Высокий',
            self::PRIORITY_URGENT => 'Срочный',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PERSONAL => 'Личная',
            self::TYPE_MANAGER_ASSIGNED => 'От руководителя',
            self::TYPE_DELEGATED => 'Делегированная',
            self::TYPE_DEPARTMENT => 'Отдел',
            self::TYPE_SYSTEM => 'Системная',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Открыта',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_REVIEW => 'На проверке',
            self::STATUS_COMPLETED => 'Готово',
            self::STATUS_CANCELLED => 'Отменена',
            self::STATUS_ON_HOLD => 'HOLD',
            self::STATUS_ARCHIVED => 'Архив',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->where('status', '!=', self::STATUS_ARCHIVED);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNotNull('archived_at')
                ->orWhere('status', self::STATUS_ARCHIVED);
        });
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function holdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hold_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TaskParticipant::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(TaskHold::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TaskActivityLog::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::priorityOptions()[$this->priority] ?? $this->priority;
    }
}
