<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Telegram\TelegramBotService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (
            ! $order->wasChanged('status')
            && ! $order->wasChanged('payment_status')
            && ! $order->wasChanged('fulfillment_status')
        ) {
            return;
        }

        $customer = $order->customer;

        if (! $customer?->telegram_id || ! $customer->telegram_verified_at) {
            return;
        }

        $telegram = app(TelegramBotService::class);

        if ($order->wasChanged('status')) {
            $telegram->sendMessage($customer->telegram_id, $this->statusMessage($order));
        }

        if ($order->wasChanged('payment_status')) {
            $telegram->sendMessage($customer->telegram_id, $this->paymentMessage($order));
        }

        if ($order->wasChanged('fulfillment_status')) {
            $telegram->sendMessage($customer->telegram_id, $this->fulfillmentMessage($order));
        }
    }

    private function statusMessage(Order $order): string
    {
        return "📦 Никтрейд\n\n"
            . "Заказ {$order->order_number}\n\n"
            . "Статус заказа изменён:\n"
            . $order->getStatusLabel();
    }

    private function paymentMessage(Order $order): string
    {
        return "💳 Никтрейд\n\n"
            . "Заказ {$order->order_number}\n\n"
            . "Статус оплаты:\n"
            . $order->getPaymentStatusLabel();
    }

    private function fulfillmentMessage(Order $order): string
    {
        if ($order->fulfillment_method === Order::FULFILLMENT_PICKUP) {
            return match ($order->fulfillment_status) {
                Order::FULFILLMENT_STATUS_READY_FOR_PICKUP => $this->pickupReadyMessage($order),
                Order::FULFILLMENT_STATUS_PICKED_UP => "✅ Никтрейд\n\n"
                    . "Заказ {$order->order_number}\n\n"
                    . "Заказ выдан. Спасибо за покупку!",
                default => "🏬 Никтрейд\n\n"
                    . "Заказ {$order->order_number}\n\n"
                    . "Самовывоз:\n"
                    . $order->getFulfillmentStatusLabel(),
            };
        }

        return "🚚 Никтрейд\n\n"
            . "Заказ {$order->order_number}\n\n"
            . "Получение:\n"
            . $order->getFulfillmentStatusLabel();
    }

    private function pickupReadyMessage(Order $order): string
    {
        return "🏬 Никтрейд\n\n"
            . "Ваш заказ готов к выдаче.\n\n"
            . "Заказ {$order->order_number}\n\n"
            . "Пункт самовывоза:\n"
            . ($order->warehouse_name_snapshot ?: '—') . "\n\n"
            . "Адрес:\n"
            . ($order->warehouse_address_snapshot ?: '—') . "\n\n"
            . "Режим работы:\n"
            . ($order->warehouse_working_hours_snapshot ?: '—');
    }
}
