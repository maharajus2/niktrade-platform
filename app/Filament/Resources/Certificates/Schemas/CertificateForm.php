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

                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Select::make('certificate_type')
                    ->label('Тип сертификата')
                    ->options([
                        'declaration' => 'Декларация',
                        'certificate' => 'Сертификат',
                        'iso' => 'ISO',
                        'sgr' => 'СГР',
                    ])
                    ->searchable(),

                TextInput::make('number')
                    ->label('Номер')
                    ->maxLength(255),

                DatePicker::make('issued_at')
                    ->label('Дата выдачи'),

                DatePicker::make('expires_at')
                    ->label('Действует до'),

                TextInput::make('issuer')
                    ->label('Орган сертификации')
                    ->maxLength(255),

                /*
                 * Поле загрузки PDF.
                 *
                 * Видно всегда:
                 * - до сохранения можно выбрать файл;
                 * - если файл выбран ошибочно, его можно удалить крестиком;
                 * - после сохранения файл можно заменить.
                 */
                FileUpload::make('file_path')
                    ->label('PDF-файл')
                    ->disk('public')
                    ->directory('certificates')
                    ->acceptedFileTypes([
                        'application/pdf',
                    ])
                    ->maxSize(20480)
                    ->openable()
                    ->downloadable()
                    ->deletable()
                    ->previewable(false)
                    ->columnSpanFull()
                    ->helperText(
                        'Загрузите PDF-файл сертификата. Максимальный размер файла — 20 МБ.'
                    ),

                /*
                 * Живой предпросмотр ДО сохранения.
                 *
                 * Работает через браузер:
                 * URL.createObjectURL(file)
                 *
                 * Не использует /storage/tmp, поэтому не ловит 404.
                 */
                ViewField::make('file_live_preview')
                    ->label('Предпросмотр выбранного PDF')
                    ->view('filament.forms.components.pdf-live-preview')
                    ->visible(fn ($get) => filled($get('file_path')) && str_starts_with($get('file_path'), 'tmp/'))
                    ->columnSpanFull(),

                /*
                 * Красивая карточка документа.
                 *
                 * Показывается только после сохранения,
                 * когда файл уже лежит в storage/app/public/certificates.
                 */
                ViewField::make('file_card')
                    ->label('')
                    ->view('filament.forms.components.pdf-card')
                    ->visible(fn ($get) => filled($get('file_path')) && ! str_starts_with($get('file_path'), 'tmp/'))
                    ->columnSpanFull(),

                /*
                 * Предпросмотр PDF ПОСЛЕ сохранения.
                 */
                ViewField::make('file_preview')
                    ->label('Предпросмотр PDF')
                    ->view('filament.forms.components.pdf-preview')
                    ->visible(fn ($get) => filled($get('file_path')) && ! str_starts_with($get('file_path'), 'tmp/'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}