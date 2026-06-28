<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'description',
    'manager_id',
    'acting_manager_id',
    'parent_id',
    'is_active',
    'sort_order',
])]
class Department extends Model
{
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function actingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_manager_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getActiveHead(): ?User
    {
        return $this->actingManager ?: $this->manager;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
