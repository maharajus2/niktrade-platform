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

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isAudio(): bool
    {
        return str_starts_with((string) $this->mime_type, 'audio/');
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isPreviewable(): bool
    {
        return $this->isImage() || $this->isAudio() || $this->isVideo();
    }

    public function getKindAttribute(): string
    {
        return match (true) {
            $this->isImage() => 'image',
            $this->isAudio() => 'audio',
            $this->isVideo() => 'video',
            $this->isPdf() => 'pdf',
            default => 'document',
        };
    }

    public function getDisplaySizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 1, '.', ' ') . ' ' . $units[$index];
    }

    public function iconName(): string
    {
        return match ($this->kind) {
            'image' => 'image',
            'audio' => 'music',
            'video' => 'video',
            'pdf' => 'file-text',
            default => 'file',
        };
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
