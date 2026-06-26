<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    public function sendMessage(string|int $chatId, string $text, array $options = []): bool
    {
        return $this->request('sendMessage', array_merge($options, [
            'chat_id' => $chatId,
            'text' => $text,
        ]), [
            'chat_id' => (string) $chatId,
        ]);
    }

    public function answerCallbackQuery(string $callbackQueryId): bool
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
        ]);
    }

    private function request(string $method, array $payload, array $logContext = []): bool
    {
        $botToken = (string) Config::get('services.telegram.bot_token', '');

        if ($botToken === '') {
            Log::warning('Telegram request was not sent: bot token is not configured.', $logContext + [
                'method' => $method,
            ]);

            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/{$method}", $payload);
        } catch (Throwable $exception) {
            Log::warning('Telegram request failed.', $logContext + [
                'method' => $method,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful() || ! (bool) $response->json('ok')) {
            Log::warning('Telegram request returned unsuccessful response.', $logContext + [
                'method' => $method,
                'status' => $response->status(),
                'description' => $response->json('description'),
            ]);

            return false;
        }

        return true;
    }
}
