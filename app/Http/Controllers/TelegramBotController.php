<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTelegramLinkToken;
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

        if (str_starts_with($text, '/start') && $chatId !== '') {
            $payload = trim(substr($text, strlen('/start')));

            if ($payload !== '') {
                $this->handleTelegramLink($payload, $from, $chatId, $telegram);

                return response()->json(['ok' => true]);
            }

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

    private function handleTelegramLink(string $payload, array $from, string $chatId, TelegramBotClient $telegram): void
    {
        $token = CustomerTelegramLinkToken::query()
            ->with('customer')
            ->where('token', $payload)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $token) {
            $telegram->sendMessage($chatId, 'Ссылка устарела. Вернитесь в личный кабинет и попробуйте снова.');

            return;
        }

        $telegramUserId = (string) ($from['id'] ?? '');

        if ($telegramUserId === '') {
            $telegram->sendMessage($chatId, 'Ссылка устарела. Вернитесь в личный кабинет и попробуйте снова.');

            return;
        }

        $alreadyLinked = Customer::query()
            ->where('telegram_id', $telegramUserId)
            ->where('id', '!=', $token->customer_id)
            ->exists();

        if ($alreadyLinked) {
            $telegram->sendMessage($chatId, 'Этот Telegram уже привязан к другому аккаунту.');

            return;
        }

        $token->customer->update([
            'telegram_id' => $telegramUserId,
            'telegram_username' => $from['username'] ?? null,
            'telegram_first_name' => $from['first_name'] ?? null,
            'telegram_last_name' => $from['last_name'] ?? null,
            'telegram_verified_at' => now(),
        ]);

        $token->update([
            'used_at' => now(),
        ]);

        $telegram->sendMessage($chatId, 'Telegram успешно подключён к вашему аккаунту Никтрейд.');
    }
}
