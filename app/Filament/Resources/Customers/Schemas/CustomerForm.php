<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label('Фамилия')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        DatePicker::make('birthday')
                            ->label('Дата рождения'),
                    ])
                    ->columns(2),

                Section::make('Настройки')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        Toggle::make('accepts_marketing')
                            ->label('Согласен на маркетинг')
                            ->default(false),

                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
