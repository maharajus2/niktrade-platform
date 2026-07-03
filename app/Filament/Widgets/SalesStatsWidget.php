<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->hasRole('super_admin') || $user->can('widgets.sales.view'));
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Всего заказов', Order::query()->count())
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Новые заказы', Order::query()->where('status', Order::STATUS_NEW)->count())
                ->icon('heroicon-o-sparkles')
                ->color('warning'),

            Stat::make('Всего покупателей', Customer::query()->count())
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Быстрая регистрация', Customer::query()->where('is_quick_registered', true)->count())
                ->icon('heroicon-o-bolt')
                ->color('gray'),
        ];
    }
}
