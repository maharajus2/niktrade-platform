<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'title',
    'category',
    'file_path',
    'comment',
    'uploaded_at',
])]
class AdminUserDocument extends Model
{
    public const CATEGORY_PASSPORT = 'passport';

    public const CATEGORY_EMPLOYMENT_CONTRACT = 'employment_contract';

    public const CATEGORY_NDA = 'nda';

    public const CATEGORY_MEDICAL_BOOK = 'medical_book';

    public const CATEGORY_DRIVER_LICENSE = 'driver_license';

    public const CATEGORY_OTHER = 'other';

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_PASSPORT => 'Паспорт',
            self::CATEGORY_EMPLOYMENT_CONTRACT => 'Трудовой договор',
            self::CATEGORY_NDA => 'NDA',
            self::CATEGORY_MEDICAL_BOOK => 'Медицинская книжка',
            self::CATEGORY_DRIVER_LICENSE => 'Водительское удостоверение',
            self::CATEGORY_OTHER => 'Другое',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }
}
