<?php

namespace App\Models;

use App\Models\Concerns\HasApprovalWorkflow;
use App\Services\Approvals\ApprovalWorkflowService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'employee_id',
    'type',
    'status',
    'start_date',
    'end_date',
    'is_all_day',
    'starts_at',
    'ends_at',
    'title',
    'request_reason_type',
    'vacation_without_pay',
    'reason',
    'manager_comment',
    'requested_by',
    'reviewed_by',
    'reviewed_at',
    'created_schedule_entries_count',
    'archived_at',
    'archived_by',
    'deleted_at',
    'deleted_by',
])]
class EmployeeScheduleRequest extends Model
{
    use HasApprovalWorkflow;

    public const TYPE_DAY_OFF = 'day_off_request';

    public const TYPE_SHIFT = 'shift_request';

    public const TYPE_VACATION = 'vacation_request';

    public const TYPE_SICK_LEAVE = 'sick_leave_request';

    public const TYPE_CUSTOM = 'custom_event_request';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_FORWARDED = 'forwarded';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_ARCHIVE = 'archive';

    public const LIFECYCLE_DELETED = 'deleted';

    public const REASON_TIME_OFF = 'time_off';

    public const REASON_FAMILY = 'family';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_DAY_OFF => 'Выходной',
            self::TYPE_SHIFT => 'Смена',
            self::TYPE_VACATION => 'Отпуск',
            self::TYPE_SICK_LEAVE => 'Больничный / отсутствие',
            self::TYPE_CUSTOM => 'Другое событие',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает рассмотрения',
            self::STATUS_IN_REVIEW => 'На рассмотрении',
            self::STATUS_FORWARDED => 'Передано на согласование',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_REJECTED => 'Отклонено',
            self::STATUS_RETURNED => 'Возвращено сотруднику',
            self::STATUS_CANCELLED => 'Отменено',
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getTypeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? 'Заявка';
    }

    public function getReasonLabel(): ?string
    {
        if ($this->type === self::TYPE_VACATION && $this->vacation_without_pay) {
            return 'Без сохранения';
        }

        if (blank($this->request_reason_type)) {
            return null;
        }

        return self::reasonOptions()[$this->request_reason_type] ?? null;
    }

    public function getStatusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? 'Неизвестно';
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_RETURNED => 'warning',
            self::STATUS_FORWARDED,
            self::STATUS_IN_REVIEW => 'info',
            self::STATUS_CANCELLED => 'gray',
            default => 'warning',
        };
    }

    public function getDateRangeLabel(): string
    {
        $start = $this->start_date?->format('d.m.Y') ?? '—';
        $end = $this->end_date?->format('d.m.Y') ?? $start;

        return $start === $end ? $start : "{$start} — {$end}";
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReviewed(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_REVIEW,
            self::STATUS_FORWARDED,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_REVIEW,
            self::STATUS_FORWARDED,
            self::STATUS_RETURNED,
        ], true);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null && $this->deleted_at === null;
    }

    public function isDeletedState(): bool
    {
        return $this->deleted_at !== null;
    }

    public function canBeArchived(): bool
    {
        return $this->isCompleted()
            && $this->archived_at === null
            && $this->deleted_at === null;
    }

    public function canBeMovedToDeleted(): bool
    {
        return $this->archived_at !== null && $this->deleted_at === null;
    }

    public function approve(User $reviewer, string $comment): int
    {
        if (! $this->canBeReviewed()) {
            return 0;
        }

        return app(ApprovalWorkflowService::class)->approveFinally($this, $reviewer, $comment);
    }

    public function reject(User $reviewer, string $comment): void
    {
        if (! $this->canBeReviewed()) {
            return;
        }

        app(ApprovalWorkflowService::class)->reject($this, $reviewer, $comment);
    }

    public function returnToRequester(User $reviewer, string $comment): void
    {
        if (! $this->canBeReviewed()) {
            return;
        }

        app(ApprovalWorkflowService::class)->returnToRequester($this, $reviewer, $comment);
    }

    public function forwardTo(User $reviewer, User $nextApprover, string $comment): void
    {
        if (! $this->canBeReviewed()) {
            return;
        }

        app(ApprovalWorkflowService::class)->forward($this, $reviewer, $nextApprover, $comment);
    }

    public function cancel(User $reviewer, ?string $comment = null): void
    {
        if (! $this->canBeCancelled()) {
            return;
        }

        app(ApprovalWorkflowService::class)->cancel($this, $reviewer, $comment);
    }

    public function archive(User $actor): void
    {
        if (! $this->canBeArchived()) {
            return;
        }

        app(ApprovalWorkflowService::class)->archive($this, $actor);
    }

    public function moveToDeleted(User $actor): void
    {
        if (! $this->canBeMovedToDeleted()) {
            return;
        }

        app(ApprovalWorkflowService::class)->moveToDeleted($this, $actor);
    }

    public function restoreFromDeleted(User $actor): void
    {
        if (! $this->isDeletedState()) {
            return;
        }

        app(ApprovalWorkflowService::class)->restore($this, $actor);
    }

    public function calendarEntryType(): string
    {
        return match ($this->type) {
            self::TYPE_DAY_OFF => EmployeeScheduleEntry::TYPE_DAY_OFF,
            self::TYPE_VACATION => EmployeeScheduleEntry::TYPE_VACATION,
            self::TYPE_SICK_LEAVE => EmployeeScheduleEntry::TYPE_SICK_LEAVE,
            self::TYPE_CUSTOM => EmployeeScheduleEntry::TYPE_CUSTOM,
            default => EmployeeScheduleEntry::TYPE_SHIFT,
        };
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin') || $user->can('employees.schedule_requests.view') || $user->can('workflow.view')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where(function (Builder $query) use ($user): void {
                    $query
                        ->where('employee_id', $user->getKey())
                        ->whereNull('deleted_at');
                })
                ->orWhere(function (Builder $query) use ($user): void {
                    $query
                        ->whereNull('deleted_at')
                        ->where(function (Builder $query) use ($user): void {
                            $query
                                ->whereHas('approvalWorkflow', fn (Builder $query): Builder => $query->where('current_approver_id', $user->getKey()))
                                ->orWhereHas('employee', fn (Builder $query): Builder => $query->where('manager_id', $user->getKey()));
                        });
                });
        });
    }

    public function handleApprovalWorkflowApproved(User $reviewer, ?string $comment = null): int
    {
        $created = $this->createScheduleEntries($reviewer);

        $this->forceFill([
            'created_schedule_entries_count' => $created,
        ])->save();

        return $created;
    }

    private function createScheduleEntries(User $reviewer): int
    {
        $created = 0;
        $entryType = $this->calendarEntryType();
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $data = [
                'employee_id' => $this->employee_id,
                'type' => $entryType,
                'title' => $this->scheduleEntryTitle($entryType),
                'is_all_day' => $this->is_all_day,
                'date' => $date->toDateString(),
                'starts_at' => $this->is_all_day ? null : $this->starts_at,
                'ends_at' => $this->is_all_day ? null : $this->ends_at,
                'request_reason_type' => $this->request_reason_type,
                'vacation_without_pay' => $this->vacation_without_pay,
                'comment' => $this->reason,
                'visibility' => EmployeeScheduleEntry::VISIBILITY_HR,
                'source' => EmployeeScheduleEntry::SOURCE_REQUEST,
                'approved_request_id' => $this->getKey(),
            ];

            $exists = EmployeeScheduleEntry::query()
                ->active()
                ->where('employee_id', $data['employee_id'])
                ->whereDate('date', $data['date'])
                ->where('type', $data['type'])
                ->where('title', $data['title'])
                ->where('is_all_day', $data['is_all_day'])
                ->where('starts_at', $data['starts_at'])
                ->where('ends_at', $data['ends_at'])
                ->where('request_reason_type', $data['request_reason_type'])
                ->where('vacation_without_pay', $data['vacation_without_pay'])
                ->exists();

            if ($exists) {
                continue;
            }

            EmployeeScheduleEntry::query()->create($data + [
                'created_by' => $reviewer->getKey(),
                'updated_by' => $reviewer->getKey(),
            ]);

            $created++;
        }

        return $created;
    }

    private function scheduleEntryTitle(string $entryType): ?string
    {
        if ($entryType === EmployeeScheduleEntry::TYPE_CUSTOM) {
            return $this->title;
        }

        if ($entryType === EmployeeScheduleEntry::TYPE_DAY_OFF && filled($this->request_reason_type)) {
            return self::reasonOptions()[$this->request_reason_type] ?? null;
        }

        if ($entryType === EmployeeScheduleEntry::TYPE_VACATION && $this->vacation_without_pay) {
            return 'Отпуск без сохранения';
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_all_day' => 'boolean',
            'vacation_without_pay' => 'boolean',
            'reviewed_at' => 'datetime',
            'created_schedule_entries_count' => 'integer',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
