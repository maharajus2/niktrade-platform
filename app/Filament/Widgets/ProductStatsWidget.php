<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ($user->hasRole('super_admin') || $user->can('widgets.products.view'));
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Всего товаров', Product::query()->count())
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),

            Stat::make('Активные товары', Product::query()->where('is_active', true)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
