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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Фото')
                    ->disk('public')
                    ->circular(),

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

                TextColumn::make('employee_status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => User::employeeStatusOptions()[$state] ?? 'Не указан')
                    ->color(fn (?string $state): string => User::employeeStatusColor($state))
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Последний вход')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Будет доступно позже'),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('archived_at')
                    ->label('Показывать архив')
                    ->placeholder('Активные')
                    ->trueLabel('Все сотрудники')
                    ->falseLabel('Только активные')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query): Builder => $query,
                        false: fn (Builder $query): Builder => $query->whereNull('archived_at'),
                        blank: fn (Builder $query): Builder => $query->whereNull('archived_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                UserResource::archiveAction(),
            ]);
    }
}
