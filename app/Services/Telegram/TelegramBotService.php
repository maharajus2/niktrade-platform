<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    public function sendMessage(string|int $chatId, string $text): bool
    {
        $botToken = (string) Config::get('services.telegram.bot_token', '');

        if ($botToken === '') {
            Log::warning('Telegram message was not sent: bot token is not configured.', [
                'chat_id' => (string) $chatId,
            ]);

            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Telegram message send failed.', [
                'chat_id' => (string) $chatId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful() || ! (bool) $response->json('ok')) {
            Log::warning('Telegram message send returned unsuccessful response.', [
                'chat_id' => (string) $chatId,
                'status' => $response->status(),
                'description' => $response->json('description'),
            ]);

            return false;
        }

        return true;
    }
}
