<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['roles', 'manager', 'department']))
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Фото')
                    ->disk('public')
                    ->imageWidth(40)
                    ->imageHeight(52)
                    ->extraImgAttributes([
                        'style' => 'background: #ffffff; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12); object-fit: cover;',
                    ]),

                TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                    ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                    ->color(fn (string $state): string => AdminRoles::color($state))
                    ->separator(', '),

                TextColumn::make('employment_status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentStatusLabel())
                    ->color(fn (?string $state, User $record): string => User::employeeStatusColor($record->employment_status ?? $record->employee_status))
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentTypeLabel())
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label('Руководитель')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('department.name')
                    ->label('Отдел')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('documents_status')
                    ->label('Документы')
                    ->badge()
                    ->state(fn (User $record): string => UserResource::documentStatusLabel($record))
                    ->color(fn (User $record): string => UserResource::documentStatusColor($record))
                    ->visible(fn (): bool => UserResource::canViewEmployeeDocuments())
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Все отделы')
                    ->relationship('department', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->orderBy('sort_order')
                        ->orderBy('name'))
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('archived_at')
                    ->label('Показывать архив')
                    ->placeholder('Активные')
                    ->trueLabel('Все сотрудники')
                    ->falseLabel('Только активные')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query): Builder => $query,
                        false: fn (Builder $query): Builder => $query
                            ->whereNull('archived_at')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('employment_status')
                                    ->orWhere('employment_status', '!=', User::STATUS_ARCHIVED);
                            }),
                        blank: fn (Builder $query): Builder => $query
                            ->whereNull('archived_at')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('employment_status')
                                    ->orWhere('employment_status', '!=', User::STATUS_ARCHIVED);
                            }),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                UserResource::archiveAction(),
            ]);
    }
}
