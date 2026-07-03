<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.resources.departments.components.department-dashboard')
                    ->visible(fn (?Department $record): bool => $record !== null)
                    ->viewData(fn (Department $record): array => ['department' => $record])
                    ->columnSpanFull(),

                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('Код')
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Сортировка')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Иерархия')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Родительский отдел')
                            ->relationship('parent', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->orderBy('sort_order')
                                ->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('manager_id')
                            ->label('Руководитель')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('acting_manager_id')
                            ->label('ВРиО руководителя')
                            ->relationship('actingManager', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
