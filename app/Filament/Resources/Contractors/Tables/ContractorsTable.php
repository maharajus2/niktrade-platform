<?php

namespace App\Filament\Resources\Contractors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContractorsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company_name')->label('Компания')->searchable(['company_name', 'inn', 'email'])->sortable(),
            TextColumn::make('inn')->label('ИНН')->sortable(),
            TextColumn::make('kpp')->label('КПП'),
            TextColumn::make('discount_percent')->label('Скидка')->numeric(decimalPlaces: 2)->suffix('%')->sortable(),
            TextColumn::make('manager.name')->label('Менеджер')->placeholder('Не назначен')->sortable(),
            IconColumn::make('is_active')->label('Активен')->boolean(),
            TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            TernaryFilter::make('is_active')->label('Активен'),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
