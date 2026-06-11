<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Certificate;

class NoExpiredCertificates implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        return ! Certificate::query()
            ->whereIn('id', $value)
            ->where('is_permanent', false)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now())
            ->exists();
    }

    public function message(): string
    {
        return 'Выбран недействительный или просроченный сертификат.';
    }
}
