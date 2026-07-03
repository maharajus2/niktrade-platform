<?php

namespace App\Filament\Pages;

use App\Support\Dashboard\DashboardWidgetRegistry;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Главная';

    protected static ?string $navigationLabel = 'Главная';

    public static function getNavigationLabel(): string
    {
        return DashboardWidgetRegistry::isHrDashboard(auth()->user())
            ? 'Рабочий стол'
            : parent::getNavigationLabel();
    }

    public function getColumns(): array|int
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return DashboardWidgetRegistry::isHrDashboard(auth()->user())
            ? 'Рабочий стол'
            : parent::getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! DashboardWidgetRegistry::isHrDashboard(auth()->user())) {
            return null;
        }

        return 'Добро пожаловать, '.auth()->user()->name.' · '.now()->translatedFormat('d F Y');
    }

    public function getWidgets(): array
    {
        return DashboardWidgetRegistry::widgetsFor(auth()->user());
    }
}
