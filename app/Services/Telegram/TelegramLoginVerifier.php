<?php

namespace App\Services\Telegram;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class TelegramLoginVerifier
{
    public function verify(array $payload): bool
    {
        $hash = (string) ($payload['hash'] ?? '');
        $authDate = (int) ($payload['auth_date'] ?? 0);
        $botToken = (string) Config::get('services.telegram.bot_token', '');

        if ($hash === '' || $authDate <= 0 || $botToken === '') {
            return false;
        }

        if ($this->isExpired($authDate)) {
            return false;
        }

        $data = collect($payload)
            ->except('hash')
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->sortKeys()
            ->map(fn ($value, string $key): string => $key . '=' . $value)
            ->implode("\n");

        $secretKey = hash('sha256', $botToken, true);
        $calculatedHash = hash_hmac('sha256', $data, $secretKey);

        return hash_equals($calculatedHash, $hash);
    }

    private function isExpired(int $authDate): bool
    {
        $maxAge = (int) Config::get('services.telegram.login_max_age', 86400);
        $authenticatedAt = Carbon::createFromTimestamp($authDate);

        if ($authenticatedAt->greaterThan(now()->addMinute())) {
            return true;
        }

        return $authenticatedAt->addSeconds($maxAge)->isPast();
    }
}
