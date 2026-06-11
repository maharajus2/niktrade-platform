<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Product;

class Certificate extends Model
{
    /*
     * Какие поля разрешено сохранять через формы.
     *
     * Filament использует массовое заполнение (mass assignment).
     * Если поля нет в fillable, Laravel не даст его сохранить.
     */
    protected $fillable = [
        'name',              // Название сертификата
        'certificate_type',  // Тип сертификата (ЕАС, ISO и т.д.)
        'number',            // Номер сертификата
        'issued_at',         // Дата выдачи
        'expires_at',        // Дата окончания действия
        'is_permanent',      // Бессрочно
        'issuer',            // Орган сертификации
        'file_path',         // Путь к PDF-файлу
        'is_active',         // Активен ли сертификат
    ];

    /*
     * Автоматическое преобразование типов.
     *
     * Без этого даты придут как строки.
     * С этим Laravel будет работать с ними как с объектами Carbon.
     */
    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Certificate $certificate) {
            if ($certificate->is_permanent) {
                $certificate->expires_at = null;
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps();
    }
}