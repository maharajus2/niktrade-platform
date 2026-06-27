<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                    ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                    ->color(fn (string $state): string => AdminRoles::color($state))
                    ->separator(', '),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => UserResource::canDelete($record)),
            ]);
    }
}
