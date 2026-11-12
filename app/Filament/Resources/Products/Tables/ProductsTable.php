<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\ProductDisplayFormatter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'nik-products-table'])
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->wrap()
                    ->width('36%')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('productLine.name')
                    ->label('Линейка')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('productType.name')
                    ->label('Тип')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Категория')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('article')
                    ->label('Артикул')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->toggleable(),

                TextColumn::make('business_price')
                    ->label('Цена для бизнеса')
                    ->money('RUB')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('availability_status')
                    ->label('Наличие')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'in_stock' => 'В наличии',
                        'out_of_stock' => 'Временно отсутствует',
                        'discontinued' => 'Снят с производства',
                        default => '—',
                    })
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('discount_percent')
                    ->label('Скидка (%)')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount_label')
                    ->label('Метка')
                    ->state(function ($record): string {
                        if ($record->discount_percent > 0) {
                            return 'Скидка '.round($record->discount_percent, 1).'%';
                        }

                        if (\filled($record->discounted_price) && $record->price > 0 && $record->discounted_price < $record->price) {
                            return 'Акция';
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        if ($record->discount_percent > 0 || (\filled($record->discounted_price) && $record->price > 0 && $record->discounted_price < $record->price)) {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('direction')
                    ->label('Направление')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'home' => 'Для дома',
                        'professional' => 'Профессиональная',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('volume_value')
                    ->label('Объем')
                    ->formatStateUsing(fn ($record): string => filled($record->volume_value)
                        ? ProductDisplayFormatter::formatVolume($record->volume_value, $record->volume_unit)
                        : '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('barcode')
                    ->label('Штрихкод')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('seo_title')
                    ->label('Заголовок для поиска')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Выгодно')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_new')
                    ->label('Новинка')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_best_seller')
                    ->label('Хит продаж')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('createFromExisting')
                        ->label('Создать на основе')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->url(fn (Product $record): string => ProductResource::getUrl('create', ['source' => $record]))
                        ->visible(fn (): bool => ProductResource::canCreate()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
