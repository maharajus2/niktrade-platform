<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Support\UploadLimits;
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
                    ->label('Фон плитки категории')
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->storeFiles(true)
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(UploadLimits::IMAGE_MAX_KILOBYTES)
                    ->previewable(true)
                    ->imagePreviewHeight('160')
                    ->openable()
                    ->downloadable()
                    ->helperText('Фон вертикальной плитки категории на главной странице. Рекомендуемый размер: 600x800 px (вертикальный формат 3:4), WebP/JPG/PNG до 15 МБ. Название категории выводится сверху, поэтому верх картинки лучше оставить спокойным. Без картинки плитка получит цветной фон.'),

                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]);
    }
}
