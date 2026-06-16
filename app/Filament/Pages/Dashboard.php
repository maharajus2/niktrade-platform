<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AnalyticsPlaceholderWidget;
use App\Filament\Widgets\ProductStatsWidget;
use App\Filament\Widgets\RussianTimeWidget;
use App\Filament\Widgets\SalesStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Главная';

    protected static ?string $navigationLabel = 'Главная';

    public function getColumns(): array|int
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            RussianTimeWidget::class,
            ProductStatsWidget::class,
            SalesStatsWidget::class,
            AnalyticsPlaceholderWidget::class,
        ];
    }
}
