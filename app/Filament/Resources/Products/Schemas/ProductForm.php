<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Certificate;
use App\Rules\NoExpiredCertificates;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->schema([
                // MAIN CONTENT AREA (2/3 width)
                Section::make('Основная информация')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255),

                        TextInput::make('article')
                            ->label('Артикул')
                            ->maxLength(255),

                        TextInput::make('barcode')
                            ->label('Штрихкод')
                            ->maxLength(255)
                            ->helperText('Введите цифры штрихкода. Изображение штрихкода будет генерироваться автоматически.'),

                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('product_line_id')
                            ->label('Линейка продуктов')
                            ->relationship('productLine', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('product_type_id')
                            ->label('Тип продукта')
                            ->relationship('productType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('direction')
                            ->label('Направление')
                            ->options([
                                'home' => 'Home',
                                'professional' => 'Professional',
                            ])
                            ->searchable(),
                    ]),

                Section::make('Коммерция')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('volume_value')
                            ->label('Объем (число)')
                            ->numeric()
                            ->minValue(0),

                        Select::make('volume_unit')
                            ->label('Единица объема')
                            ->options([
                                'ml' => 'мл',
                                'l' => 'л',
                                'g' => 'г',
                                'kg' => 'кг',
                            ])
                            ->searchable(),

                        TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0),

                        TextInput::make('discount_percent')
                            ->label('Скидка (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ]),

                Section::make('Описание товара')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Textarea::make('short_description')
                            ->label('Краткое описание')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('composition')
                            ->label('Состав')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('usage_method')
                            ->label('Способ применения')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('storage_conditions')
                            ->label('Условия хранения')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Срок годности')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('shelf_life_value')
                            ->label('Срок годности (число)')
                            ->numeric()
                            ->minValue(0),

                        Select::make('shelf_life_unit')
                            ->label('Единица срока годности')
                            ->options([
                                'days' => 'Дней',
                                'months' => 'Месяцев',
                                'years' => 'Лет',
                            ])
                            ->searchable(),
                    ]),

                Section::make('Документация')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Select::make('certificate_ids')
                            ->label('Сертификаты')
                            ->multiple()
                            ->relationship('certificates', 'name')
                            ->options(function () {
                                return Certificate::query()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($certificate) => [
                                        $certificate->id => sprintf(
                                            '%s %s%s — %s',
                                            $certificate->expires_at && $certificate->expires_at->isPast()
                                                ? '🔴'
                                                : ($certificate->expires_at && $certificate->expires_at->lte(now()->addDays(30))
                                                    ? '🟡'
                                                    : '🟢'),
                                            $certificate->name,
                                            $certificate->number ? " №{$certificate->number}" : '',
                                            $certificate->expires_at && $certificate->expires_at->isPast()
                                                ? 'просрочен'
                                                : ($certificate->expires_at && $certificate->expires_at->lte(now()->addDays(30))
                                                    ? 'скоро истекает'
                                                    : 'действует')
                                        ),
                                    ]);
                            })
                            ->rules([new NoExpiredCertificates()])
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        FileUpload::make('instruction_file_path')
                            ->label('Инструкция (PDF)')
                            ->disk('public')
                            ->directory('product-instructions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Галерея')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Repeater::make('images')
                            ->label('Изображения')
                            ->relationship('images')
                            ->orderable('sort_order')
                            ->createItemButtonLabel('Добавить изображение')
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('Файл изображения')
                                    ->required()
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->previewable()
                                    ->imagePreviewHeight('250')
                                    ->columnSpanFull(),

                                TextInput::make('alt')
                                    ->label('Alt текст')
                                    ->maxLength(255),

                                TextInput::make('sort_order')
                                    ->label('Порядок')
                                    ->numeric()
                                    ->default(0),

                                Toggle::make('is_main')
                                    ->label('Главное изображение')
                                    ->default(false),
                            ])
                            ->columnSpanFull(),
                    ]),

                // RIGHT SIDEBAR (1/3 width)
                Section::make('Публикация и метки')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->columnSpanFull(),

                        Toggle::make('is_featured')
                            ->label('Выгодно')
                            ->columnSpanFull(),

                        Toggle::make('is_new')
                            ->label('Новинка')
                            ->columnSpanFull(),

                        Toggle::make('is_best_seller')
                            ->label('Хит продаж')
                            ->columnSpanFull(),

                        TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
