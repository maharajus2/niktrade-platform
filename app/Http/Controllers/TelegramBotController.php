<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Telegram\TelegramBotClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramBotController extends Controller
{
    public function webhook(Request $request, TelegramBotClient $telegram): JsonResponse
    {
        $webhookSecret = (string) config('services.telegram.webhook_secret', '');

        if ($webhookSecret !== '' && ! hash_equals($webhookSecret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $message = $request->input('message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');
        $from = $message['from'] ?? [];
        $telegramUserId = (string) ($from['id'] ?? '');

        if ($text === '/start' && $chatId !== '') {
            $customer = $telegramUserId !== ''
                ? Customer::query()->where('telegram_id', $telegramUserId)->first()
                : null;

            if ($customer) {
                $customer->update([
                    'telegram_username' => $from['username'] ?? $customer->telegram_username,
                    'telegram_first_name' => $from['first_name'] ?? $customer->telegram_first_name,
                    'telegram_last_name' => $from['last_name'] ?? $customer->telegram_last_name,
                    'telegram_verified_at' => $customer->telegram_verified_at ?? now(),
                ]);

                $telegram->sendMessage($chatId, 'Бот подключён. Теперь вы можете запросить код подтверждения телефона на сайте.');
            } else {
                $telegram->sendMessage($chatId, 'Бот запущен. Вернитесь на сайт и подтвердите Telegram в личном кабинете.');
            }
        }

        return response()->json(['ok' => true]);
    }
}
