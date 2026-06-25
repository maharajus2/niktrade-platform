<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('city')
                    ->label('Город')
                    ->required()
                    ->maxLength(255),

                TextInput::make('address')
                    ->label('Адрес')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->maxLength(255),

                TextInput::make('working_hours')
                    ->label('Часы работы')
                    ->maxLength(255),

                Textarea::make('comment')
                    ->label('Комментарий')
                    ->rows(3),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),

                TextInput::make('sort_order')
                    ->label('Сортировка')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
