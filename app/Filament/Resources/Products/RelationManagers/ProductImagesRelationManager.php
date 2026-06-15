<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Галерея';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('Файл изображения')
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->storeFiles(true)
                    ->image()
                    ->previewable(true)
                    ->imagePreviewHeight('160')
                    ->openable()
                    ->downloadable()
                    ->required(),

                TextInput::make('alt')
                    ->label('Alt текст')
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_main')
                    ->label('Главное изображение')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_path')
                    ->label('Изображение')
                    ->disk('public')
                    ->height(60)
                    ->square(),

                TextColumn::make('alt')
                    ->label('Alt текст')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),

                IconColumn::make('is_main')
                    ->label('Главное')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
