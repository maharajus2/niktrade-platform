<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidInn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A(?!0+\z)(?:[0-9]{10}|[0-9]{12})\z/', $value)) {
            $fail('Некорректный ИНН');

            return;
        }

        $digit = static function (array $weights) use ($value): int {
            $sum = 0;
            foreach ($weights as $index => $weight) {
                $sum += $weight * (int) $value[$index];
            }

            return $sum % 11 % 10;
        };

        $valid = strlen($value) === 10
            ? $digit([2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $value[9]
            : $digit([7, 2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $value[10]
                && $digit([3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8]) === (int) $value[11];

        if (! $valid) {
            $fail('Некорректный ИНН');
        }
    }
}
