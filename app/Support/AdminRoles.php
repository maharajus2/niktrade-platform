<?php

namespace App\Support;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class AdminRoles
{
    private const LABELS = [
        'super_admin' => 'Суперадминистратор',
        'admin' => 'Администратор',
        'order_manager' => 'Менеджер заказов',
        'picker' => 'Сборщик',
        'courier' => 'Курьер',
        'pickup_operator' => 'Оператор самовывоза',
        'content_manager' => 'Контент-менеджер',
        'accountant' => 'Бухгалтер',
        'hr' => 'HR',
    ];

    private const DESCRIPTIONS = [
        'super_admin' => 'Полный доступ ко всей системе.',
        'admin' => 'Управление магазином и операционной деятельностью.',
        'order_manager' => 'Работа с заказами и покупателями.',
        'picker' => 'Комплектация заказов.',
        'courier' => 'Доставка заказов.',
        'pickup_operator' => 'Выдача заказов со склада.',
        'content_manager' => 'Каталог, товары и сертификаты.',
        'accountant' => 'Оплаты и финансовые операции.',
        'hr' => 'Кадровая работа, сотрудники, документы, графики и заявки.',
    ];

    private const COLORS = [
        'super_admin' => 'danger',
        'admin' => 'primary',
        'order_manager' => 'info',
        'picker' => 'warning',
        'courier' => 'success',
        'pickup_operator' => 'purple',
        'content_manager' => 'gray',
        'accountant' => 'emerald',
        'hr' => 'purple',
    ];

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? Str::of($role)->replace('_', ' ')->headline()->toString();
    }

    public static function description(string $role): string
    {
        return self::DESCRIPTIONS[$role] ?? 'Административная роль.';
    }

    public static function icon(string $role): Heroicon
    {
        return match ($role) {
            'super_admin' => Heroicon::OutlinedShieldCheck,
            'admin' => Heroicon::OutlinedCog6Tooth,
            'order_manager' => Heroicon::OutlinedClipboardDocumentList,
            'picker' => Heroicon::OutlinedArchiveBox,
            'courier' => Heroicon::OutlinedTruck,
            'pickup_operator' => Heroicon::OutlinedBuildingStorefront,
            'content_manager' => Heroicon::OutlinedSquares2x2,
            'accountant' => Heroicon::OutlinedBanknotes,
            'hr' => Heroicon::OutlinedUserGroup,
            default => Heroicon::OutlinedUser,
        };
    }

    public static function color(string $role): string
    {
        return self::COLORS[$role] ?? 'gray';
    }

    public static function primaryRoleName(?User $user): ?string
    {
        return $user?->roles->first()?->name;
    }

    public static function primaryLabel(?User $user): string
    {
        $role = self::primaryRoleName($user);

        return $role ? self::label($role) : 'Роль не назначена';
    }

    public static function primaryDescription(?User $user): string
    {
        $role = self::primaryRoleName($user);

        return $role ? self::description($role) : 'Назначьте сотруднику роль, чтобы определить его доступы.';
    }

    public static function primaryIcon(?User $user): Heroicon
    {
        $role = self::primaryRoleName($user);

        return $role ? self::icon($role) : Heroicon::OutlinedUserGroup;
    }

    public static function primaryColor(?User $user): string
    {
        $role = self::primaryRoleName($user);

        return $role ? self::color($role) : 'gray';
    }
}
