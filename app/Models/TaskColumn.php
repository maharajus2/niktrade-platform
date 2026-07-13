<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'board_id',
    'name',
    'slug',
    'color',
    'sort_order',
    'is_final',
    'is_hold',
])]
class TaskColumn extends Model
{
    public const SLUG_NEW = 'new';

    public const SLUG_IN_PROGRESS = 'in_progress';

    public const SLUG_REVIEW = 'review';

    public const SLUG_DONE = 'done';

    protected function casts(): array
    {
        return [
            'is_final' => 'boolean',
            'is_hold' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'column_id');
    }
}
