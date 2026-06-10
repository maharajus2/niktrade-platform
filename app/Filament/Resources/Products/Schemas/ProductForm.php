<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
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
                    ->preload()
                    ->required(),

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
                    ->label('Популярный'),

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
