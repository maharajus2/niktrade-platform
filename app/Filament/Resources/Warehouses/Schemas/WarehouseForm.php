<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Сортировка')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Section::make('Адрес')
                    ->schema([
                        TextInput::make('postal_code')
                            ->label('Индекс')
                            ->maxLength(255),

                        TextInput::make('region')
                            ->label('Область / регион')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('Город')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('street')
                            ->label('Улица')
                            ->maxLength(255),

                        TextInput::make('house')
                            ->label('Дом / здание')
                            ->maxLength(255),

                        TextInput::make('building')
                            ->label('Корпус / строение')
                            ->maxLength(255),

                        TextInput::make('premises')
                            ->label('Помещение / офис / склад')
                            ->maxLength(255),

                        TextInput::make('address')
                            ->label('Адрес, legacy')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Режим работы')
                    ->schema([
                        CheckboxList::make('working_days')
                            ->label('Дни работы')
                            ->options([
                                'mon' => 'Пн',
                                'tue' => 'Вт',
                                'wed' => 'Ср',
                                'thu' => 'Чт',
                                'fri' => 'Пт',
                                'sat' => 'Сб',
                                'sun' => 'Вс',
                            ])
                            ->columns(7)
                            ->columnSpanFull(),

                        TextInput::make('working_time_from')
                            ->label('С')
                            ->placeholder('09:00')
                            ->maxLength(255),

                        TextInput::make('working_time_to')
                            ->label('До')
                            ->placeholder('18:00')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Контакты и комментарий')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->maxLength(255),

                        TextInput::make('working_hours')
                            ->label('Режим работы, legacy')
                            ->maxLength(255),

                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
