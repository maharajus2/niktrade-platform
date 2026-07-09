<?php

namespace App\Filament\Resources\SiteHomepageBanners\Tables;

use App\Models\SiteHomepageBanner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiteHomepageBannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Изображение')
                    ->disk('public')
                    ->height(54)
                    ->square(),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subtitle')
                    ->label('Подзаголовок')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('link_type')
                    ->label('Ссылка')
                    ->formatStateUsing(fn (?string $state): string => SiteHomepageBanner::linkTypeOptions()[$state] ?? 'Каталог')
                    ->badge(),

                TextColumn::make('theme')
                    ->label('Тема')
                    ->formatStateUsing(fn (?string $state): string => SiteHomepageBanner::themeOptions()[$state] ?? 'Синий')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('С')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ends_at')
                    ->label('До')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активен'),

                SelectFilter::make('theme')
                    ->label('Тема')
                    ->options(SiteHomepageBanner::themeOptions()),
            ])
            ->defaultSort('sort_order')
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
