<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'type',
    'title',
    'description',
    'owner_id',
    'department_id',
    'related_type',
    'related_id',
    'is_system',
    'is_archived',
    'last_message_at',
    'created_by',
])]
class Conversation extends Model
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';
    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_OBJECT = 'object';
    public const TYPE_SYSTEM = 'system';

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'muted_until', 'is_pinned', 'is_archived', 'added_by'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('messenger.view_all')) {
            return $query;
        }

        return $query->whereHas('participants', fn (Builder $query): Builder => $query->where('user_id', $user->id));
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isDepartment(): bool
    {
        return $this->type === self::TYPE_DEPARTMENT;
    }

    public function displayTitleFor(User $user): string
    {
        if (! $this->isDirect()) {
            return $this->title ?: 'Без названия';
        }

        $other = $this->users
            ->first(fn (User $participant): bool => (int) $participant->id !== (int) $user->id);

        return $other?->name ?: 'Личный чат';
    }

    public function unreadCountFor(User $user): int
    {
        $participant = $this->participants
            ->first(fn (ConversationParticipant $participant): bool => (int) $participant->user_id === (int) $user->id)
            ?: $this->participants()->where('user_id', $user->id)->first();

        if (! $participant instanceof ConversationParticipant) {
            return 0;
        }

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->when(
                $participant->last_read_at,
                fn (Builder $query): Builder => $query->where('created_at', '>', $participant->last_read_at),
            )
            ->when(
                ! $participant->last_read_at,
                fn (Builder $query): Builder => $query->where('created_at', '>', $participant->joined_at ?? $this->created_at),
            )
            ->count();
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_archived' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }
}
