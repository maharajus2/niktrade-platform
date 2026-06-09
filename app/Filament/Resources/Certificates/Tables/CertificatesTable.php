<?php

namespace App\Filament\Resources\Certificates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                 * Название сертификата.
                 */
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                /*
                 * Тип сертификата.
                 */
                TextColumn::make('certificate_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'declaration' => 'Декларация',
                        'certificate' => 'Сертификат',
                        'iso' => 'ISO',
                        'sgr' => 'СГР',
                        default => '—',
                    })
                    ->sortable(),

                /*
                 * Номер документа.
                 */
                TextColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->toggleable(),

                /*
                 * Дата окончания действия.
                 */
                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->date('d.m.Y')
                    ->sortable(),

                /*
                 * Статус срока действия.
                 *
                 * Логика:
                 * - даты нет: срок не указан;
                 * - дата меньше сегодняшней: истёк;
                 * - дата в ближайшие 30 дней: скоро истекает;
                 * - иначе: действует.
                 */
                TextColumn::make('certificate_status')
                    ->label('Статус')
                    ->state(function ($record): string {
                        if (! $record->expires_at) {
                            return 'Срок не указан';
                        }

                        if ($record->expires_at->isPast()) {
                            return 'Истёк';
                        }

                        if ($record->expires_at->lte(now()->addDays(30))) {
                            return 'Скоро истекает';
                        }

                        return 'Действует';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        if (! $record->expires_at) {
                            return 'gray';
                        }

                        if ($record->expires_at->isPast()) {
                            return 'danger';
                        }

                        if ($record->expires_at->lte(now()->addDays(30))) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                /*
                 * Активен ли сертификат.
                 */
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                /*
                 * Дата создания записи.
                 */
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                /*
                 * Фильтр по активности.
                 */
                TernaryFilter::make('is_active')
                    ->label('Активен'),

                /*
                 * Сертификаты, у которых дата окончания уже прошла.
                 */
                Filter::make('expired')
                    ->label('Истёкшие')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '<', now())),

                /*
                 * Сертификаты, которые истекают в ближайшие 30 дней.
                 */
                Filter::make('expires_soon')
                    ->label('Истекают в ближайшие 30 дней')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '>=', now())
                        ->whereDate('expires_at', '<=', now()->addDays(30))),
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