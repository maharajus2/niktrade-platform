<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramBotClient
{
    public function sendMessage(string $chatId, string $text): bool
    {
        $botToken = (string) Config::get('services.telegram.bot_token', '');

        if ($botToken === '') {
            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
        } catch (Throwable) {
            return false;
        }

        return $response->successful() && (bool) $response->json('ok');
    }
}
