<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                 * Название сертификата.
                 */
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                /*
                 * Тип сертификата.
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
                 * Орган, который выдал сертификат.
                 */
                TextInput::make('issuer')
                    ->label('Орган сертификации')
                    ->maxLength(255),

                /*
                 * Загрузка PDF-файла сертификата.
                 */
                FileUpload::make('file_path')
                    ->label('PDF-файл')
                    ->disk('public')
                    ->directory('certificates')
                    ->acceptedFileTypes([
                        'application/pdf',
                    ]),

                /*
                 * Предпросмотр PDF.
                 *
                 * Показывается только после того,
                 * как у записи уже есть загруженный файл.
                 */
                ViewField::make('file_preview')
                    ->label('Предпросмотр PDF')
                    ->view('filament.forms.components.pdf-preview')
                    ->visible(fn ($get) => filled($get('file_path'))),

                /*
                 * Активен ли сертификат.
                 */
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}