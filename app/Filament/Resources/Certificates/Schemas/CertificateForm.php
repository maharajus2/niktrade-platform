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
                 * Этот компонент не использует /storage/tmp.
                 * Он читает выбранный файл прямо в браузере.
                 */
                ViewField::make('file_live_preview')
                    ->label('')
                    ->view('filament.forms.components.pdf-live-preview')
                    ->columnSpanFull(),

                /*
                 * Красивая карточка документа.
                 *
                 * Показываем только после сохранения,
                 * когда путь начинается с certificates/.
                 */
                ViewField::make('file_card')
                    ->label('')
                    ->view('filament.forms.components.pdf-card')
                    ->visible(fn ($get) => filled($get('file_path')) && str_starts_with($get('file_path'), 'certificates/'))
                    ->columnSpanFull(),

                /*
                 * Предпросмотр PDF ПОСЛЕ сохранения.
                 */
                ViewField::make('file_preview')
                    ->label('Предпросмотр PDF')
                    ->view('filament.forms.components.pdf-preview')
                    ->visible(fn ($get) => filled($get('file_path')) && str_starts_with($get('file_path'), 'certificates/'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}