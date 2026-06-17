<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\OrderItem;
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

                TextColumn::make('weight_snapshot_value')
                    ->label('Вес товара')
                    ->formatStateUsing(fn ($state, OrderItem $record): string => self::formatItemWeight($record)),

                TextColumn::make('line_weight_grams')
                    ->label('Вес позиции')
                    ->state(fn (OrderItem $record): ?int => $record->getLineWeightGrams())
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? $state . ' г' : '—'),

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

    private static function formatItemWeight(OrderItem $record): string
    {
        if ($record->weight_snapshot_value === null) {
            return '—';
        }

        $value = rtrim(rtrim(number_format((float) $record->weight_snapshot_value, 3, ',', ' '), '0'), ',');
        $unit = match ($record->weight_snapshot_unit) {
            'kg' => 'кг',
            default => 'г',
        };

        return $value . ' ' . $unit;
    }
}
