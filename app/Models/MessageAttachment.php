<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'message_id',
    'uploaded_by',
    'source',
    'file_path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'related_type',
    'related_id',
    'title',
])]
class MessageAttachment extends Model
{
    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_EMPLOYEE_DOCUMENT = 'employee_document';
    public const SOURCE_ORDER_DOCUMENT = 'order_document';
    public const SOURCE_TASK_ATTACHMENT = 'task_attachment';
    public const SOURCE_SYSTEM_DOCUMENT = 'system_document';
    public const SOURCE_FUTURE_MAIL_ATTACHMENT = 'future_mail_attachment';

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
