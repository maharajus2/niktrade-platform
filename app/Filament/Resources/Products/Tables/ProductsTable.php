<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('productLine.name')
                    ->label('Линейка')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('productType.name')
                    ->label('Тип продукта')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('article')
                    ->label('Артикул')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Популярный')
                    ->boolean(),

                IconColumn::make('is_new')
                    ->label('Новинка')
                    ->boolean(),

                IconColumn::make('is_best_seller')
                    ->label('Хит продаж')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
