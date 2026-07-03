<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'approvable_type',
    'approvable_id',
    'status',
    'submitted_by',
    'current_approver_id',
    'completed_at',
])]
class ApprovalWorkflow extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_FORWARDED = 'forwarded';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

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

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowEvent::class);
    }

    public function getStatusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? 'Неизвестно';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
