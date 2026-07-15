<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('image_path')
                    ->label('Изображение для главной')
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->storeFiles(true)
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->previewable(true)
                    ->imagePreviewHeight('160')
                    ->openable()
                    ->downloadable()
                    ->helperText('Используется в карточке категории на главной странице catalog2. Рекомендуемый размер: 480x320 px, WebP/JPG/PNG до 2 МБ.'),

                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]);
    }
}
