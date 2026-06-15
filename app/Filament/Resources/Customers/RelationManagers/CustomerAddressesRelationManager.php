<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Адреса доставки';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Адрес')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('Индекс')
                            ->maxLength(255),

                        TextInput::make('region')
                            ->label('Регион')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('Город')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('street')
                            ->label('Улица')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('house')
                            ->label('Дом')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Детали доставки')
                    ->schema([
                        TextInput::make('building')
                            ->label('Корпус')
                            ->maxLength(255),

                        TextInput::make('apartment')
                            ->label('Квартира')
                            ->maxLength(255),

                        TextInput::make('entrance')
                            ->label('Подъезд')
                            ->maxLength(255),

                        TextInput::make('floor')
                            ->label('Этаж')
                            ->maxLength(255),

                        Toggle::make('is_default')
                            ->label('Адрес по умолчанию')
                            ->default(false),

                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('street')
                    ->label('Улица')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('house')
                    ->label('Дом')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apartment')
                    ->label('Квартира')
                    ->toggleable(),

                IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
