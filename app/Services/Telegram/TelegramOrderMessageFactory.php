<?php

namespace App\Services\Telegram;

use App\Models\Order;

class TelegramOrderMessageFactory
{
    public function orderCreated(Order $order): string
    {
        $lines = [
            '📦 <b>Заказ принят</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            'Сумма: <b>' . $this->e($this->formatMoney($order->total)) . '</b>',
            'Способ получения: <b>' . $this->e($order->getFulfillmentMethodLabel()) . '</b>',
        ];

        if ($order->fulfillment_method === Order::FULFILLMENT_PICKUP) {
            $this->appendPickupPoint($lines, $order);
        } else {
            $address = $this->deliveryAddress($order);

            if ($address !== '') {
                $lines[] = '';
                $lines[] = 'Адрес:';
                $lines[] = $this->e($address);
            }
        }

        $lines[] = '';
        $lines[] = 'Мы сообщим, когда заказ начнут собирать.';

        return implode("\n", $lines);
    }

    public function statusChanged(Order $order): string
    {
        $lines = [
            '📦 <b>Статус заказа изменён</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            'Новый статус: <b>' . $this->e($order->getStatusLabel()) . '</b>',
        ];

        $nextStep = $this->statusNextStep($order->status);

        if ($nextStep !== null) {
            $lines[] = '';
            $lines[] = $this->e($nextStep);
        }

        return implode("\n", $lines);
    }

    public function paymentStatusChanged(Order $order): string
    {
        return implode("\n", [
            '💳 <b>Статус оплаты изменён</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            'Оплата: <b>' . $this->e($order->getPaymentStatusLabel()) . '</b>',
        ]);
    }

    public function fulfillmentStatusChanged(Order $order): string
    {
        if ($order->fulfillment_method === Order::FULFILLMENT_PICKUP) {
            return match ($order->fulfillment_status) {
                Order::FULFILLMENT_STATUS_READY_FOR_PICKUP => $this->pickupReady($order),
                Order::FULFILLMENT_STATUS_PICKED_UP => $this->pickupPickedUp($order),
                default => $this->pickupStatus($order),
            };
        }

        return implode("\n", [
            '🚚 <b>Получение заказа</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            'Статус: <b>' . $this->e($order->getFulfillmentStatusLabel()) . '</b>',
        ]);
    }

    public function orderKeyboard(Order $order): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => 'Открыть заказ', 'url' => $this->orderUrl($order)],
                ],
                [
                    ['text' => 'Каталог', 'url' => $this->catalogUrl()],
                ],
            ],
        ];
    }

    public function orderUrl(Order $order): string
    {
        return $this->accountUrl('/orders/' . $order->id);
    }

    public function catalogUrl(): string
    {
        return $this->siteUrl('/catalog');
    }

    public function accountUrl(string $path = ''): string
    {
        return $this->siteUrl('/account' . $path);
    }

    public function formatMoney(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ₽';
    }

    private function pickupReady(Order $order): string
    {
        $lines = [
            '🏬 <b>Заказ готов к выдаче</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
        ];

        $this->appendPickupPoint($lines, $order);

        return implode("\n", $lines);
    }

    private function pickupPickedUp(Order $order): string
    {
        return implode("\n", [
            '✅ <b>Заказ выдан</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            '',
            'Спасибо за покупку!',
        ]);
    }

    private function pickupStatus(Order $order): string
    {
        return implode("\n", [
            '🏬 <b>Получение заказа</b>',
            '',
            'Заказ: <b>' . $this->e($order->order_number) . '</b>',
            'Статус: <b>' . $this->e($order->getFulfillmentStatusLabel()) . '</b>',
        ]);
    }

    private function appendPickupPoint(array &$lines, Order $order): void
    {
        $lines[] = '';
        $lines[] = 'Пункт самовывоза:';
        $lines[] = '<b>' . $this->e($order->warehouse_name_snapshot ?: '—') . '</b>';

        if ($order->warehouse_address_snapshot) {
            $lines[] = $this->e($order->warehouse_address_snapshot);
        }

        if ($order->warehouse_working_hours_snapshot) {
            $lines[] = $this->e($order->warehouse_working_hours_snapshot);
        }
    }

    private function deliveryAddress(Order $order): string
    {
        return collect([
            $order->postal_code,
            $order->region,
            $order->city,
            $order->street,
            $order->house,
            $order->building,
            $order->apartment,
        ])->filter()->implode(', ');
    }

    private function statusNextStep(?string $status): ?string
    {
        return match ($status) {
            Order::STATUS_NEW => 'Заказ принят и ожидает обработки.',
            Order::STATUS_ASSEMBLING => 'Заказ начали собирать.',
            Order::STATUS_READY_FOR_DISPATCH => 'Заказ готов к отгрузке.',
            Order::STATUS_COMPLETED => 'Заказ завершён. Спасибо за покупку!',
            Order::STATUS_CANCELLED => 'Заказ отменён.',
            default => null,
        };
    }

    private function siteUrl(string $path): string
    {
        return 'https://niktrade.ru' . $path;
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
