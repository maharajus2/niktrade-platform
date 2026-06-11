<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Certificate;
use App\Rules\NoExpiredCertificates;
use Filament\Forms\Components\Select;
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
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255),

                TextInput::make('article')
                    ->label('Артикул')
                    ->maxLength(255),

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

                Select::make('direction')
                    ->label('Назначение')
                    ->options([
                        'home' => 'Для дома',
                        'professional' => 'Профессиональное',
                    ])
                    ->searchable(),

                Select::make('certificate_ids')
                    ->label('Документация')
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
                    ->preload(),

                Repeater::make('images')
                    ->label('Галерея')
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

                FileUpload::make('instruction_file_path')
                    ->label('Инструкция (PDF)')
                    ->disk('public')
                    ->directory('product-instructions')
                    ->acceptedFileTypes(['application/pdf'])
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),

                TextInput::make('barcode')
                    ->label('Штрихкод')
                    ->maxLength(255)
                    ->helperText('Введите цифры штрихкода. Изображение штрихкода будет генерироваться автоматически.'),

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

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),

                Toggle::make('is_featured')
                    ->label('Выгодно'),

                Toggle::make('is_new')
                    ->label('Новинка'),

                Toggle::make('is_best_seller')
                    ->label('Хит продаж'),

                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
