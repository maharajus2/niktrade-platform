<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('country')
                    ->label('Страна')
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }
}