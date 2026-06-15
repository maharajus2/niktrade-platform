<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

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
