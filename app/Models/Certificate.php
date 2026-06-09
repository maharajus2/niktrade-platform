<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'is_active' => 'boolean',
    ];
}