<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerPhoneVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): View
    {
        return view('customer-account.phone-verification', [
            'customer' => $request->user('customer'),
            'telegramBotUsername' => ltrim((string) config('services.telegram.bot_username'), '@'),
        ]);
    }

    public function requestCode(Request $request, TelegramBotClient $telegram): RedirectResponse
    {
        $customer = $request->user('customer');

        if (! $customer->phone) {
            return back()->withErrors([
                'phone_verification' => 'Укажите телефон в профиле перед подтверждением.',
            ]);
        }

        if (! $customer->telegram_id) {
            return back()->withErrors([
                'phone_verification' => 'Сначала подтвердите Telegram-аккаунт и откройте бота.',
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $customer->phoneVerifications()->create([
            'phone' => $customer->phone,
            'code' => $code,
            'telegram_chat_id' => $customer->telegram_id,
            'telegram_user_id' => $customer->telegram_id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $sent = $telegram->sendMessage(
            $customer->telegram_id,
            "Код подтверждения телефона: {$code}\nОн действует 10 минут.",
        );

        if (! $sent) {
            return back()->withErrors([
                'phone_verification' => 'Не удалось отправить код. Откройте Telegram-бота, нажмите Start и попробуйте ещё раз.',
            ]);
        }

        return back()->with('success', 'Код отправлен в Telegram.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $customer = $request->user('customer');
        $verification = $customer->phoneVerifications()
            ->where('phone', $customer->phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $verification) {
            return back()->withErrors([
                'code' => 'Нет активного кода подтверждения или срок действия истёк.',
            ]);
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            return back()->withErrors([
                'code' => 'Превышено количество попыток. Запросите новый код.',
            ]);
        }

        $verification->increment('attempts');

        if (! hash_equals($verification->code, $data['code'])) {
            return back()->withErrors([
                'code' => 'Неверный код подтверждения.',
            ]);
        }

        $verification->update([
            'verified_at' => now(),
        ]);

        $customer->update([
            'phone_verified_at' => now(),
        ]);

        return redirect()
            ->route('customer.account')
            ->with('success', 'Телефон подтверждён.');
    }
}
