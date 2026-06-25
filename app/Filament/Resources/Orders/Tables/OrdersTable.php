<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
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
                    ->sortable()
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
                    ->html()
                    ->getStateUsing(fn (Order $record) => $record->getSlaBadgeHtml())
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

                SelectFilter::make('sla')
                    ->label('Контроль срока')
                    ->options([
                        Order::SLA_STATE_OVERDUE => 'Просроченные',
                        Order::SLA_STATE_WARNING => 'Скоро просрочатся',
                        Order::SLA_STATE_OK => 'В срок',
                        Order::SLA_STATE_COMPLETED => 'Завершённые',
                        Order::SLA_STATE_NONE => 'Отменённые',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? Order::applySlaFilter($query, $data['value'])
                        : $query),
            ])
            ->recordClasses(fn (Order $record): string => match ($record->getSlaState()) {
                Order::SLA_STATE_OVERDUE => '!bg-red-50',
                Order::SLA_STATE_WARNING => '!bg-yellow-50',
                Order::SLA_STATE_COMPLETED,
                Order::SLA_STATE_NONE => '!bg-gray-50',
                default => '',
            })
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
