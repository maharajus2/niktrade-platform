<?php

namespace App\Models;

use App\Support\EmployeeRequiredDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'employee_id',
    'category',
    'title',
    'document_number',
    'issued_at',
    'expires_at',
    'issued_by',
    'file_path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'comment',
    'uploaded_by',
    'replaced_by_id',
    'archived_at',
])]
class EmployeeDocument extends Model
{
    public const EXPIRATION_NO_EXPIRATION = 'no_expiration';

    public const EXPIRATION_VALID = 'valid';

    public const EXPIRATION_WARNING_30 = 'warning_30';

    public const EXPIRATION_WARNING_14 = 'warning_14';

    public const EXPIRATION_WARNING_7 = 'warning_7';

    public const EXPIRATION_WARNING_1 = 'warning_1';

    public const EXPIRATION_EXPIRED = 'expired';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function previousVersions(): HasMany
    {
        return $this->hasMany(self::class, 'replaced_by_id');
    }

    public function getCategoryLabel(): string
    {
        return EmployeeRequiredDocuments::label($this->category);
    }

    public function hasExpiration(): bool
    {
        return $this->expires_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->hasExpiration() && $this->expires_at->isBefore(today());
    }

    public function expiresSoon(): bool
    {
        return in_array($this->getExpirationState(), [
            self::EXPIRATION_WARNING_30,
            self::EXPIRATION_WARNING_14,
            self::EXPIRATION_WARNING_7,
            self::EXPIRATION_WARNING_1,
        ], true);
    }

    public function getExpirationState(): string
    {
        if (! $this->hasExpiration()) {
            return self::EXPIRATION_NO_EXPIRATION;
        }

        if ($this->isExpired()) {
            return self::EXPIRATION_EXPIRED;
        }

        $days = today()->diffInDays($this->expires_at, false);

        return match (true) {
            $days <= 1 => self::EXPIRATION_WARNING_1,
            $days <= 7 => self::EXPIRATION_WARNING_7,
            $days <= 14 => self::EXPIRATION_WARNING_14,
            $days <= 30 => self::EXPIRATION_WARNING_30,
            default => self::EXPIRATION_VALID,
        };
    }

    public function getExpirationLabel(): string
    {
        return match ($this->getExpirationState()) {
            self::EXPIRATION_NO_EXPIRATION => 'Без срока',
            self::EXPIRATION_VALID => 'Действует',
            self::EXPIRATION_WARNING_30 => 'Истекает до 30 дней',
            self::EXPIRATION_WARNING_14 => 'Истекает до 14 дней',
            self::EXPIRATION_WARNING_7 => 'Истекает до 7 дней',
            self::EXPIRATION_WARNING_1 => 'Истекает завтра',
            self::EXPIRATION_EXPIRED => 'Просрочен',
            default => 'Не указан',
        };
    }

    public function expirationColor(): string
    {
        return match ($this->getExpirationState()) {
            self::EXPIRATION_VALID => 'success',
            self::EXPIRATION_WARNING_30, self::EXPIRATION_WARNING_14 => 'warning',
            self::EXPIRATION_WARNING_7, self::EXPIRATION_WARNING_1 => 'danger',
            self::EXPIRATION_EXPIRED => 'danger',
            default => 'gray',
        };
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'archived_at' => 'datetime',
        ];
    }
}
