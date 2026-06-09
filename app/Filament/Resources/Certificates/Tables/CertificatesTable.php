<?php

namespace App\Filament\Resources\Certificates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                 * Название сертификата.
                 *
                 * Главная колонка списка.
                 */
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                /*
                 * Тип сертификата.
                 *
                 * Показываем не техническое значение,
                 * а понятную русскую подпись.
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
                 * Активен ли сертификат.
                 */
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                /*
                 * Дата создания записи в админке.
                 */
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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