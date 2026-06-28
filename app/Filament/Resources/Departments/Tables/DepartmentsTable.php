<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentsTable
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

                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('description')
                    ->label('Описание')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parent.name')
                    ->label('Родительский отдел')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('manager.name')
                    ->label('Руководитель')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('actingManager.name')
                    ->label('ВРиО')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('employees_count')
                    ->label('Сотрудники')
                    ->counts('employees')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Department $record): bool => DepartmentResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => DepartmentResource::canUseDepartmentPermission('departments.delete')),
                ]),
            ]);
    }
}
