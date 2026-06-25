<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Support\WeightFormatter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Покупатель')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Номер заказа')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_first_name')
                            ->label('Имя')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_last_name')
                            ->label('Фамилия')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('phone')
                            ->label('Телефон')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->columnSpan(1),

                Section::make('Статусы')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options(Order::statusOptions())
                            ->required(),

                        Select::make('payment_status')
                            ->label('Оплата')
                            ->options([
                                Order::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
                                Order::PAYMENT_STATUS_PAID => 'Оплачен',
                                Order::PAYMENT_STATUS_FAILED => 'Ошибка оплаты',
                                Order::PAYMENT_STATUS_REFUNDED => 'Возврат',
                            ])
                            ->required(),

                        Select::make('delivery_status')
                            ->label('Доставка')
                            ->options([
                                Order::DELIVERY_STATUS_NOT_SHIPPED => 'Не отправлен',
                                Order::DELIVERY_STATUS_SHIPPED => 'Отправлен',
                                Order::DELIVERY_STATUS_DELIVERED => 'Доставлен',
                                Order::DELIVERY_STATUS_READY_FOR_PICKUP => 'Готов к выдаче',
                                Order::DELIVERY_STATUS_PICKED_UP => 'Забран самовывозом',
                            ])
                            ->required(),

                        Placeholder::make('sla_current')
                            ->label('Текущий SLA')
                            ->content(fn (?Order $record): string => $record?->getSlaLabel() ?? 'Не требуется'),

                        Placeholder::make('sla_deadline')
                            ->label('Срок обработки')
                            ->content(fn (?Order $record): string => ($deadline = $record?->getSlaDeadline())
                                ? $deadline->format('d.m.Y H:i')
                                : 'Не требуется'),

                        Placeholder::make('sla_timing')
                            ->label('Время до просрочки')
                            ->content(fn (?Order $record): string => $record?->getSlaTimingLabel() ?? 'Не требуется'),

                        Placeholder::make('fulfillment_method')
                            ->label('Получение')
                            ->content(fn (?Order $record): string => $record?->getFulfillmentMethodLabel() ?? 'Доставка'),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                Section::make('Адрес доставки')
                    ->visible(fn (?Order $record): bool => ($record?->fulfillment_method ?? Order::FULFILLMENT_DELIVERY) !== Order::FULFILLMENT_PICKUP)
                    ->schema([
                        TextInput::make('postal_code')
                            ->label('Индекс')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('region')
                            ->label('Регион')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('city')
                            ->label('Город')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('street')
                            ->label('Улица')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('house')
                            ->label('Дом')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('building')
                            ->label('Корпус')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('apartment')
                            ->label('Квартира')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('entrance')
                            ->label('Подъезд')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('floor')
                            ->label('Этаж')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('delivery_comment')
                            ->label('Комментарий к доставке')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Пункт самовывоза')
                    ->visible(fn (?Order $record): bool => ($record?->fulfillment_method ?? Order::FULFILLMENT_DELIVERY) === Order::FULFILLMENT_PICKUP)
                    ->schema([
                        Placeholder::make('warehouse_name_snapshot')
                            ->label('Пункт')
                            ->content(fn (?Order $record): string => $record?->warehouse_name_snapshot ?: '—'),

                        Placeholder::make('warehouse_address_snapshot')
                            ->label('Адрес')
                            ->content(fn (?Order $record): string => $record?->warehouse_address_snapshot ?: '—'),

                        Placeholder::make('warehouse_phone_snapshot')
                            ->label('Телефон')
                            ->content(fn (?Order $record): string => $record?->warehouse_phone_snapshot ?: '—'),

                        Placeholder::make('warehouse_working_hours_snapshot')
                            ->label('Часы работы')
                            ->content(fn (?Order $record): string => $record?->warehouse_working_hours_snapshot ?: '—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Итоги')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Сумма товаров')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('discount_total')
                            ->label('Скидка')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('delivery_total')
                            ->label('Доставка')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('total')
                            ->label('Итого')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('total_weight_grams')
                            ->label('Общий вес заказа, г')
                            ->formatStateUsing(fn (?int $state): string => WeightFormatter::formatGrams($state))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),

                Section::make('Комментарий')
                    ->schema([
                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
