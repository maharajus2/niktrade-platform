<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_first_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_last_name')
                    ->label('Фамилия')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Order::statusLabel($state))
                    ->color(fn (?string $state): string => Order::statusColor($state)),

                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Order::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
                        Order::PAYMENT_STATUS_PAID => 'Оплачен',
                        Order::PAYMENT_STATUS_FAILED => 'Ошибка оплаты',
                        Order::PAYMENT_STATUS_REFUNDED => 'Возврат',
                        default => $state ?? '—',
                    }),

                TextColumn::make('delivery_status')
                    ->label('Доставка')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Order::DELIVERY_STATUS_NOT_SHIPPED => 'Не отправлен',
                        Order::DELIVERY_STATUS_SHIPPED => 'Отправлен',
                        Order::DELIVERY_STATUS_DELIVERED => 'Доставлен',
                        default => $state ?? '—',
                    }),

                TextColumn::make('sla')
                    ->label('Контроль срока')
                    ->badge()
                    ->getStateUsing(fn (Order $record): string => $record->getSlaLabel())
                    ->color(fn (Order $record): string => $record->getSlaColor()),

                TextColumn::make('total')
                    ->label('Итого')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(Order::statusOptions()),

                SelectFilter::make('payment_status')
                    ->label('Оплата')
                    ->options([
                        Order::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
                        Order::PAYMENT_STATUS_PAID => 'Оплачен',
                        Order::PAYMENT_STATUS_FAILED => 'Ошибка оплаты',
                        Order::PAYMENT_STATUS_REFUNDED => 'Возврат',
                    ]),

                SelectFilter::make('delivery_status')
                    ->label('Доставка')
                    ->options([
                        Order::DELIVERY_STATUS_NOT_SHIPPED => 'Не отправлен',
                        Order::DELIVERY_STATUS_SHIPPED => 'Отправлен',
                        Order::DELIVERY_STATUS_DELIVERED => 'Доставлен',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label('Удалить заказ')
                    ->requiresConfirmation()
                    ->modalDescription('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.'),
            ]);
    }
}
