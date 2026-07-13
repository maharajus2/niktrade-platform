<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Аватар')
                    ->disk('public')
                    ->imageWidth(44)
                    ->imageHeight(44)
                    ->extraImgAttributes([
                        'style' => 'background: linear-gradient(145deg, rgba(232, 241, 255, .95), rgba(255, 255, 255, .86)); border: 1px solid rgba(255, 255, 255, .86); border-radius: 15px; box-shadow: 0 12px 24px rgba(31, 52, 86, .12), inset 0 1px 0 rgba(255, 255, 255, .82); object-fit: cover;',
                    ]),

                TextColumn::make('first_name')
                    ->label('ФИО')
                    ->formatStateUsing(fn ($record): string => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('birthday')
                    ->label('Дата рождения')
                    ->date('d.m.Y')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                IconColumn::make('accepts_marketing')
                    ->label('Маркетинг')
                    ->boolean(),

                IconColumn::make('is_quick_registered')
                    ->label('Полная регистрация')
                    ->getStateUsing(fn ($record): bool => ! $record->is_quick_registered)
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активен'),

                TernaryFilter::make('accepts_marketing')
                    ->label('Согласен на маркетинг'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
