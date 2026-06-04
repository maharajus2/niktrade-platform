<?php

namespace App\Filament\Resources\ProductLines\Schemas;

use App\Models\Brand;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductLineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('name')
                    ->label('Название линейки')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Описание')
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]);
    }
}