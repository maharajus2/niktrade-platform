<?php

namespace App\Rules;

use App\Models\Contractor;
use App\Support\ContractorIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueContractorIdentity implements ValidationRule
{
    public function __construct(private mixed $kpp = null, private ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $inn = ContractorIdentity::inn($value);
        $kpp = is_string($inn) && strlen($inn) === 12 ? null : ContractorIdentity::kpp($this->kpp);

        if (! is_string($inn) || (! is_string($kpp) && $kpp !== null)) {
            return;
        }

        if (Contractor::query()->where('inn', $inn)->where('kpp', $kpp)
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))->exists()) {
            $fail('Компания с такими ИНН и КПП уже зарегистрирована');
        }
    }
}
