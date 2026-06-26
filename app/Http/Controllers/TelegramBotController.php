<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTelegramLinkToken;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramOrderMessageFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    public function webhook(Request $request, TelegramBotService $telegram, TelegramOrderMessageFactory $messages): JsonResponse
    {
        $webhookSecret = (string) config('services.telegram.webhook_secret', '');

        if ($webhookSecret !== '' && ! hash_equals($webhookSecret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $callbackQuery = $request->input('callback_query');

        if (is_array($callbackQuery)) {
            $this->handleCallbackQuery($callbackQuery, $telegram, $messages);

            return response()->json(['ok' => true]);
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

        if ($chatId === '') {
            return response()->json(['ok' => true]);
        }

        if (preg_match('/^\/start(?:@\w+)?(?:\s+(.*))?$/u', $text, $matches) === 1) {
            $payload = trim((string) ($matches[1] ?? ''));

            if ($payload !== '') {
                $this->handleTelegramLink($payload, $from, $chatId, $telegram);

                return response()->json(['ok' => true]);
            }

            $this->sendMainMenu($chatId, $telegram);

            return response()->json(['ok' => true]);
        }

        if (preg_match('/^\/orders(?:@\w+)?(?:\s|$)/u', $text) === 1) {
            $this->sendOrders($chatId, (string) ($from['id'] ?? ''), $telegram, $messages);

            return response()->json(['ok' => true]);
        }

        if (preg_match('/^\/help(?:@\w+)?(?:\s|$)/u', $text) === 1) {
            $this->sendHelp($chatId, $telegram);

            return response()->json(['ok' => true]);
        }

        $telegram->sendMessage(
            $chatId,
            'Я пока понимаю команды меню. Выберите действие ниже.',
            ['reply_markup' => $this->mainMenuKeyboard()],
        );

        return response()->json(['ok' => true]);
    }

    private function handleCallbackQuery(array $callbackQuery, TelegramBotService $telegram, TelegramOrderMessageFactory $messages): void
    {
        $callbackQueryId = (string) ($callbackQuery['id'] ?? '');
        $data = (string) ($callbackQuery['data'] ?? '');
        $message = $callbackQuery['message'] ?? [];
        $chatId = (string) data_get($message, 'chat.id', '');
        $from = $callbackQuery['from'] ?? [];
        $telegramUserId = (string) ($from['id'] ?? '');

        if ($callbackQueryId !== '') {
            $telegram->answerCallbackQuery($callbackQueryId);
        }

        if ($chatId === '') {
            return;
        }

        match ($data) {
            'orders' => $this->sendOrders($chatId, $telegramUserId, $telegram, $messages),
            'support' => $this->sendSupport($chatId, $telegram),
            default => $telegram->sendMessage(
                $chatId,
                'Я пока понимаю команды меню. Выберите действие ниже.',
                ['reply_markup' => $this->mainMenuKeyboard()],
            ),
        };
    }

    private function handleTelegramLink(string $payload, array $from, string $chatId, TelegramBotService $telegram): void
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

        $telegram->sendMessage($chatId, 'Telegram успешно подключён к вашему аккаунту Никтрейд.', [
            'reply_markup' => $this->mainMenuKeyboard(),
        ]);
    }

    private function sendMainMenu(string $chatId, TelegramBotService $telegram): void
    {
        $telegram->sendMessage(
            $chatId,
            "Здравствуйте! 👋\n\n"
                . "Это бот Никтрейд.\n\n"
                . "Здесь вы можете:\n"
                . "• получать уведомления о заказах;\n"
                . "• смотреть статус заказов;\n"
                . "• быстро перейти в личный кабинет;\n"
                . "• открыть каталог товаров.",
            ['reply_markup' => $this->mainMenuKeyboard()],
        );
    }

    private function sendHelp(string $chatId, TelegramBotService $telegram): void
    {
        $telegram->sendMessage(
            $chatId,
            "Команды бота:\n"
                . "/start — открыть меню\n"
                . "/orders — показать последние заказы\n"
                . "/help — помощь",
            ['reply_markup' => $this->mainMenuKeyboard()],
        );
    }

    private function sendOrders(
        string $chatId,
        string $telegramUserId,
        TelegramBotService $telegram,
        TelegramOrderMessageFactory $messages,
    ): void {
        $customer = Customer::query()
            ->where('telegram_id', $telegramUserId)
            ->whereNotNull('telegram_verified_at')
            ->first();

        if (! $customer) {
            $telegram->sendMessage(
                $chatId,
                'Telegram ещё не привязан к аккаунту. Войдите в личный кабинет и нажмите «Подключить Telegram».',
                ['reply_markup' => $this->mainMenuKeyboard()],
            );

            return;
        }

        $orders = $customer->orders()
            ->latest()
            ->limit(5)
            ->get();

        if ($orders->isEmpty()) {
            $telegram->sendMessage($chatId, 'У вас пока нет заказов.', [
                'reply_markup' => $this->mainMenuKeyboard(),
            ]);

            return;
        }

        $lines = ['📦 <b>Мои заказы</b>'];
        $keyboard = ['inline_keyboard' => []];

        foreach ($orders as $order) {
            $lines[] = '';
            $lines[] = '<b>' . $this->e($order->order_number) . '</b>';
            $lines[] = $this->e($order->getStatusLabel());
            $lines[] = $this->e($messages->formatMoney($order->total));
            $keyboard['inline_keyboard'][] = [
                ['text' => 'Открыть заказ', 'url' => $messages->orderUrl($order)],
            ];
        }

        $keyboard['inline_keyboard'][] = [
            ['text' => 'Каталог', 'url' => $messages->catalogUrl()],
            ['text' => 'Личный кабинет', 'url' => $messages->accountUrl()],
        ];

        $telegram->sendMessage($chatId, implode("\n", $lines), [
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard,
        ]);
    }

    private function sendSupport(string $chatId, TelegramBotService $telegram): void
    {
        $telegram->sendMessage(
            $chatId,
            'По вопросам заказа напишите нам на support@niktrade.ru.',
            ['reply_markup' => $this->mainMenuKeyboard()],
        );
    }

    private function mainMenuKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '📦 Мои заказы', 'callback_data' => 'orders'],
                ],
                [
                    ['text' => '🛒 Каталог', 'url' => 'https://niktrade.ru/catalog'],
                ],
                [
                    ['text' => '👤 Личный кабинет', 'url' => 'https://niktrade.ru/account'],
                ],
                [
                    ['text' => '☎️ Поддержка', 'callback_data' => 'support'],
                ],
            ],
        ];
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
