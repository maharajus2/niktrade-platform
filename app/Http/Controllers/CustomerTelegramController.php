<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerTelegramController extends Controller
{
    public function link(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $botUsername = ltrim((string) config('services.telegram.bot_username'), '@');

        if ($botUsername === '') {
            return back()->withErrors([
                'telegram' => 'Telegram-бот пока не настроен.',
            ]);
        }

        $customer->telegramLinkTokens()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $token = Str::random(48);

        $customer->telegramLinkTokens()->create([
            'token' => $token,
            'expires_at' => now()->addMinutes(15),
        ]);

        return redirect()->away("https://t.me/{$botUsername}?start={$token}");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user('customer')->update([
            'telegram_id' => null,
            'telegram_username' => null,
            'telegram_first_name' => null,
            'telegram_last_name' => null,
            'telegram_verified_at' => null,
        ]);

        return back()->with('success', 'Telegram отвязан.');
    }
}
