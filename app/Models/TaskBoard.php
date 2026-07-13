<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'owner_id',
    'department_id',
    'visibility',
    'type',
    'is_default',
    'is_archived',
    'created_by',
])]
class TaskBoard extends Model
{
    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_SHARED = 'shared';

    public const VISIBILITY_DEPARTMENT = 'department';

    public const VISIBILITY_COMPANY = 'company';

    public const TYPE_PERSONAL = 'personal';

    public const TYPE_MANAGER = 'manager';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_PROJECT = 'project';

    public const TYPE_SYSTEM = 'system';

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(TaskColumn::class, 'board_id')->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'board_id');
    }
}
