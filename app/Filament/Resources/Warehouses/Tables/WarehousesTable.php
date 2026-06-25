<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('sort_order')->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_address')
                    ->label('Адрес')
                    ->getStateUsing(fn ($record): string => $record->full_address ?: '—')
                    ->searchable(['address', 'postal_code', 'region', 'city', 'street', 'house', 'building', 'premises'])
                    ->wrap(),

                TextColumn::make('working_schedule_label')
                    ->label('Режим работы')
                    ->getStateUsing(fn ($record): string => $record->working_schedule_label ?: '—'),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable(),

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
