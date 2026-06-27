<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Models\User;
use App\Support\AdminRoles;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Роль')
                    ->icon(fn (?User $record): Heroicon => AdminRoles::primaryIcon($record))
                    ->iconColor(fn (?User $record): string => AdminRoles::primaryColor($record))
                    ->description(fn (?User $record): string => AdminRoles::primaryDescription($record))
                    ->schema([
                        Select::make('roles')
                            ->label('Роли')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => AdminRoles::label($record->name))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Данные сотрудника')
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->required(fn (?string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
