<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'user_id',
    'role',
    'joined_at',
    'last_read_at',
    'muted_until',
    'is_pinned',
    'is_archived',
    'added_by',
])]
class ConversationParticipant extends Model
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_OBSERVER = 'observer';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'muted_until' => 'datetime',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }
}
