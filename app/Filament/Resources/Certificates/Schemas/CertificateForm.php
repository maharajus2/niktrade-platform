<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                 * Название сертификата.
                 *
                 * Например:
                 * Декларация соответствия ЕАС
                 * ISO 9001
                 * СГР
                 */
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                /*
                 * Тип сертификата.
                 *
                 * Здесь сразу делаем список,
                 * чтобы сотрудники не вводили
                 * одно и то же разными способами.
                 */
                Select::make('certificate_type')
                    ->label('Тип сертификата')
                    ->options([
                        'declaration' => 'Декларация',
                        'certificate' => 'Сертификат',
                        'iso' => 'ISO',
                        'sgr' => 'СГР',
                    ])
                    ->searchable(),

                /*
                 * Номер сертификата.
                 *
                 * Например:
                 * ЕАЭС N RU Д-RU.РА01.В.12345/26
                 */
                TextInput::make('number')
                    ->label('Номер')
                    ->maxLength(255),

                /*
                 * Дата выдачи.
                 */
                DatePicker::make('issued_at')
                    ->label('Дата выдачи'),

                /*
                 * Дата окончания действия.
                 */
                DatePicker::make('expires_at')
                    ->label('Действует до'),

                /*
                 * Кто выдал сертификат.
                 *
                 * Например:
                 * РосТест
                 * Тест-С.-Петербург
                 */
                TextInput::make('issuer')
                    ->label('Орган сертификации')
                    ->maxLength(255),

                /*
                 * Загрузка PDF-файла сертификата.
                 *
                 * Позже товар сможет ссылаться
                 * на этот документ.
                 */
                FileUpload::make('file_path')
                    ->label('PDF-файл')
                    ->directory('certificates')
                    ->acceptedFileTypes([
                        'application/pdf',
                    ]),

                /*
                 * Показывать сертификат
                 * в системе или скрыть.
                 */
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}