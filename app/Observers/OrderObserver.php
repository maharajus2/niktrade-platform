<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramOrderMessageFactory;

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
        $messages = app(TelegramOrderMessageFactory::class);
        $options = [
            'parse_mode' => 'HTML',
            'reply_markup' => $messages->orderKeyboard($order),
        ];

        if ($order->wasChanged('status')) {
            $telegram->sendMessage($customer->telegram_id, $messages->statusChanged($order), $options);
        }

        if ($order->wasChanged('payment_status')) {
            $telegram->sendMessage($customer->telegram_id, $messages->paymentStatusChanged($order), $options);
        }

        if ($order->wasChanged('fulfillment_status')) {
            $telegram->sendMessage($customer->telegram_id, $messages->fulfillmentStatusChanged($order), $options);
        }
    }
}
