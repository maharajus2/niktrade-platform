<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Telegram\TelegramLoginVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerTelegramController extends Controller
{
    public function verify(Request $request, TelegramLoginVerifier $verifier): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string'],
            'username' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
            'auth_date' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        if (! $verifier->verify($data)) {
            return back()->withErrors([
                'telegram' => 'Не удалось подтвердить Telegram. Попробуйте ещё раз.',
            ]);
        }

        $customer = $request->user('customer');
        $existingCustomer = Customer::query()
            ->where('telegram_id', $data['id'])
            ->where('id', '!=', $customer->id)
            ->exists();

        if ($existingCustomer) {
            return back()->withErrors([
                'telegram' => 'Этот Telegram уже привязан к другому аккаунту.',
            ]);
        }

        $customer->update([
            'telegram_id' => $data['id'],
            'telegram_username' => $data['username'] ?? null,
            'telegram_first_name' => $data['first_name'] ?? null,
            'telegram_last_name' => $data['last_name'] ?? null,
            'telegram_verified_at' => now(),
        ]);

        return back()->with('success', 'Telegram подтверждён.');
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
