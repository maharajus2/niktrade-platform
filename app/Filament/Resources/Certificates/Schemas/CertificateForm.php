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
                 * Орган сертификации.
                 */
                TextInput::make('issuer')
                    ->label('Орган сертификации')
                    ->maxLength(255),

                /*
                 * Поле загрузки PDF.
                 *
                 * Теперь оно видно всегда:
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
                 * Красивая карточка документа.
                 *
                 * Показывается сразу после выбора файла
                 * и после сохранения записи.
                 */
                ViewField::make('file_card')
                    ->label('')
                    ->view('filament.forms.components.pdf-card')
                    ->visible(fn ($get) => filled($get('file_path')))
                    ->columnSpanFull(),

                /*
                 * Предпросмотр PDF.
                 *
                 * Показывается сразу после выбора файла
                 * и после сохранения записи.
                 */
                ViewField::make('file_preview')
                    ->label('Предпросмотр PDF')
                    ->view('filament.forms.components.pdf-preview')
                    ->visible(fn ($get) => filled($get('file_path')))
                    ->columnSpanFull(),

                /*
                 * Активен ли сертификат.
                 */
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}