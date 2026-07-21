<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'conversation_id',
    'sender_id',
    'body',
    'type',
    'reply_to_id',
    'edited_at',
    'deleted_at',
    'system_event',
    'metadata',
])]
class Message extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_TASK_LINK = 'task_link';
    public const TYPE_ORDER_LINK = 'order_link';
    public const TYPE_DOCUMENT_LINK = 'document_link';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM || filled($this->system_event);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
