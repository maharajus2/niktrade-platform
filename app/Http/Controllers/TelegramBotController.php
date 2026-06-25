<?php

namespace App\Http\Controllers;

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

        if ($text === '/start' && $chatId !== '') {
            $telegram->sendMessage(
                $chatId,
                'Здравствуйте! Это бот Никтрейд. Здесь можно будет подтвердить телефон и получать уведомления о заказах.',
            );
        }

        return response()->json(['ok' => true]);
    }
}
