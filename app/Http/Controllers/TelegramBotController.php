<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        Log::info('Telegram webhook message received', [
            'update_id' => $request->input('update_id'),
            'chat_id' => $chatId ?: null,
            'telegram_user_id' => $from['id'] ?? null,
            'username' => $from['username'] ?? null,
        ]);

        if ($text === '/start' && $chatId !== '') {
            $telegram->sendMessage(
                $chatId,
                "Здравствуйте!\n\n"
                    . "Добро пожаловать в Никтрейд.\n\n"
                    . "В ближайшее время через этого бота можно будет:\n\n"
                    . "• подтверждать телефон;\n"
                    . "• получать уведомления о заказах;\n"
                    . "• получать информацию о статусе заказа.\n\n"
                    . "Пока бот находится в разработке.",
            );
        }

        return response()->json(['ok' => true]);
    }
}
