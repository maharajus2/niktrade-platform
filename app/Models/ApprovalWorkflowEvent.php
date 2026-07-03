<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'approval_workflow_id',
    'actor_id',
    'action',
    'from_status',
    'to_status',
    'comment',
    'forwarded_to_id',
    'metadata',
])]
class ApprovalWorkflowEvent extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_RETURNED = 'returned';

    public const ACTION_FORWARDED = 'forwarded';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_MOVED_TO_DELETED = 'moved_to_deleted';

    public const ACTION_RESTORED = 'restored';

    public const ACTION_SYSTEM = 'system';

    public static function actionOptions(): array
    {
        return [
            self::ACTION_CREATED => 'Создал заявку',
            self::ACTION_APPROVED => 'Одобрено',
            self::ACTION_REJECTED => 'Отклонено',
            self::ACTION_RETURNED => 'Возвращено сотруднику',
            self::ACTION_FORWARDED => 'Передано на согласование',
            self::ACTION_CANCELLED => 'Отменено',
            self::ACTION_ARCHIVED => 'Заявка перемещена в архив',
            self::ACTION_MOVED_TO_DELETED => 'Заявка перемещена в удалённые',
            self::ACTION_RESTORED => 'Заявка восстановлена',
            self::ACTION_SYSTEM => 'Системное действие',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function forwardedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_to_id');
    }

    public function getActionLabel(): string
    {
        return self::actionOptions()[$this->action] ?? 'Действие';
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
