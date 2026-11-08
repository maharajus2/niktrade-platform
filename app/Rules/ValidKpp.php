<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidKpp implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A[0-9]{4}[0-9A-Z]{2}[0-9]{3}\z/', $value)) {
            $fail('Некорректный КПП');
        }
    }
}
