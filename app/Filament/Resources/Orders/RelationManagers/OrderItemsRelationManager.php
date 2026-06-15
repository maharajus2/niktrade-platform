<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Состав заказа';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label('Товар')
                    ->searchable(),

                TextColumn::make('product_article')
                    ->label('Артикул')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Кол-во'),

                TextColumn::make('unit_price')
                    ->label('Цена')
                    ->money('RUB'),

                TextColumn::make('discount_percent')
                    ->label('Скидка, %'),

                TextColumn::make('discounted_unit_price')
                    ->label('Цена со скидкой')
                    ->money('RUB'),

                TextColumn::make('line_total')
                    ->label('Сумма')
                    ->money('RUB'),
            ]);
    }
}
