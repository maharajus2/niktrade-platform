<?php

namespace App\Filament\Resources\SiteHomepageBanners\Schemas;

use App\Models\SiteHomepageBanner;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteHomepageBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Контент баннера')
                    ->schema([
                        TextInput::make('eyebrow')
                            ->label('Надзаголовок')
                            ->maxLength(255)
                            ->helperText('Например: Производитель бытовой химии.'),

                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('subtitle')
                            ->label('Подзаголовок')
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('badge_text')
                            ->label('Метка')
                            ->maxLength(255)
                            ->helperText('Короткая плашка на баннере: Новинка, Акция, Для бизнеса.'),

                        TextInput::make('button_label')
                            ->label('Текст кнопки')
                            ->required()
                            ->maxLength(80)
                            ->default('Перейти'),
                    ])
                    ->columns(2),

                Section::make('Ссылка и связи')
                    ->schema([
                        Select::make('link_type')
                            ->label('Куда ведет кнопка')
                            ->options(SiteHomepageBanner::linkTypeOptions())
                            ->default(SiteHomepageBanner::LINK_CATALOG)
                            ->required()
                            ->reactive(),

                        Select::make('product_id')
                            ->label('Товар')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get): bool => $get('link_type') === SiteHomepageBanner::LINK_PRODUCT),

                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get): bool => $get('link_type') === SiteHomepageBanner::LINK_BRAND),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get): bool => $get('link_type') === SiteHomepageBanner::LINK_CATEGORY),

                        TextInput::make('external_url')
                            ->label('Своя ссылка')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn ($get): bool => $get('link_type') === SiteHomepageBanner::LINK_CUSTOM),

                        Toggle::make('opens_in_new_tab')
                            ->label('Открывать в новой вкладке')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Визуал баннера')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Баннер desktop')
                            ->disk('public')
                            ->directory('homepage-banners')
                            ->visibility('public')
                            ->storeFiles(true)
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(4096)
                            ->previewable(true)
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->helperText('Размер: 1920x620 px. Формат: WebP, JPG или PNG, до 4 МБ. Текст, кнопки и часть графики сайт накладывает сам, поэтому лучше загружать чистый визуал/товар/фон без важного текста. Важные детали держите ближе к центру и правой части, не у краев.'),

                        FileUpload::make('mobile_image_path')
                            ->label('Баннер mobile')
                            ->disk('public')
                            ->directory('homepage-banners/mobile')
                            ->visibility('public')
                            ->storeFiles(true)
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(4096)
                            ->previewable(true)
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->helperText('Размер: 900x1200 px. Формат: WebP, JPG или PNG, до 4 МБ. Текст, кнопки и часть графики сайт накладывает сам, поэтому лучше загружать чистый визуал/товар/фон без важного текста. Важные детали держите ближе к центру, не у краев.'),

                        Select::make('theme')
                            ->label('Тема')
                            ->options(SiteHomepageBanner::themeOptions())
                            ->default('blue')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Публикация')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),

                        DatePicker::make('starts_at')
                            ->label('Показывать с'),

                        DatePicker::make('ends_at')
                            ->label('Показывать до'),
                    ])
                    ->columns(4),
            ]);
    }
}
