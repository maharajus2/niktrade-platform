<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BusinessRequestStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новая', self::InProgress => 'В работе', self::Invoiced => 'Счёт выставлен',
            self::Paid => 'Оплачена', self::Shipped => 'Отгружена', self::Cancelled => 'Отменена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info', self::InProgress => 'warning', self::Invoiced => 'primary',
            self::Paid, self::Shipped => 'success', self::Cancelled => 'gray',
        };
    }
}
