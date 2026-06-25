<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => Order::applySlaDefaultSort($query))
            ->columns([
                TextColumn::make('sla')
                    ->label('Контроль срока')
                    ->badge()
                    ->html()
                    ->getStateUsing(fn (Order $record) => $record->getSlaBadgeHtml())
                    ->color(fn (Order $record): string => $record->getSlaColor()),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус заказа')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Order::statusLabel($state))
                    ->color(fn (?string $state): string => Order::statusColor($state)),

                TextColumn::make('fulfillment_status')
                    ->label('Получение')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Order $record): string => Order::fulfillmentStatusLabel(
                        $state,
                        $record->fulfillment_method,
                    )),

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

                TextColumn::make('total')
                    ->label('Итого')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('customer_first_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_last_name')
                    ->label('Фамилия')
                    ->searchable()
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

                SelectFilter::make('fulfillment_status')
                    ->label('Получение')
                    ->options([
                        Order::FULFILLMENT_STATUS_NOT_SENT => 'Не отправлен',
                        Order::FULFILLMENT_STATUS_SHIPPED => 'Передан перевозчику',
                        Order::FULFILLMENT_STATUS_DELIVERED => 'Доставлен',
                        Order::FULFILLMENT_STATUS_NOT_READY => 'Не готов',
                        Order::FULFILLMENT_STATUS_READY_FOR_PICKUP => 'Готов к выдаче',
                        Order::FULFILLMENT_STATUS_PICKED_UP => 'Выдан',
                    ]),

                SelectFilter::make('sla')
                    ->label('Контроль срока')
                    ->options([
                        Order::SLA_STATE_OVERDUE => 'Просроченные',
                        Order::SLA_STATE_WARNING => 'Скоро просрочатся',
                        Order::SLA_STATE_OK => 'В срок',
                        Order::SLA_STATE_COMPLETED => 'Завершённые',
                        Order::SLA_STATE_NONE => 'Отменённые',
                        Order::SLA_STATE_ARCHIVED => 'Архив',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? Order::applySlaFilter($query, $data['value'])
                        : $query),
            ])
            ->recordClasses(fn (Order $record): string => match ($record->getSlaState()) {
                Order::SLA_STATE_OVERDUE => '!bg-red-50',
                Order::SLA_STATE_WARNING => '!bg-yellow-50',
                Order::SLA_STATE_COMPLETED,
                Order::SLA_STATE_NONE,
                Order::SLA_STATE_ARCHIVED => '!bg-gray-50',
                default => '',
            })
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('archive')
                    ->label('Отправить в архив')
                    ->requiresConfirmation()
                    ->modalDescription('Отправить заказ в архив?')
                    ->visible(fn (Order $record): bool => $record->canBeArchived())
                    ->action(fn (Order $record): bool => $record->update(['archived_at' => now()])),
                Action::make('unarchive')
                    ->label('Вернуть из архива')
                    ->requiresConfirmation()
                    ->modalDescription('Вернуть заказ из архива?')
                    ->visible(fn (Order $record): bool => $record->isArchived())
                    ->action(fn (Order $record): bool => $record->update(['archived_at' => null])),
                DeleteAction::make()
                    ->label('Удалить заказ')
                    ->requiresConfirmation()
                    ->modalDescription('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.'),
            ]);
    }
}
