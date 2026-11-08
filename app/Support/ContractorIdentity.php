<?php

namespace App\Support;

class ContractorIdentity
{
    public static function inn(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/[^0-9]/', '', trim($value)) : $value;
    }

    public static function kpp(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('/[^0-9A-Z]/', '', strtoupper(trim($value))) ?: null;
    }
}
