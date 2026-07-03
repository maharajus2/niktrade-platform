<?php

namespace App\Support\Dashboard;

use App\Filament\Widgets\AnalyticsPlaceholderWidget;
use App\Filament\Widgets\Hr\HrBirthdaysWidget;
use App\Filament\Widgets\Hr\HrEmployeeAlertsWidget;
use App\Filament\Widgets\Hr\HrKpiWidget;
use App\Filament\Widgets\Hr\HrQuickActionsWidget;
use App\Filament\Widgets\Hr\HrTodayWidget;
use App\Filament\Widgets\Hr\HrUpcomingEventsWidget;
use App\Filament\Widgets\Hr\HrWorkflowWidget;
use App\Filament\Widgets\OrderSlaStatsWidget;
use App\Filament\Widgets\ProductStatsWidget;
use App\Filament\Widgets\RussianTimeWidget;
use App\Filament\Widgets\SalesStatsWidget;
use App\Models\User;

class DashboardWidgetRegistry
{
    /** @var array<string, array<int, class-string>> */
    private static array $registeredWidgets = [];

    /**
     * @param  array<int, class-string>  $widgets
     */
    public static function register(string $dashboard, array $widgets): void
    {
        self::$registeredWidgets[$dashboard] = [
            ...(self::$registeredWidgets[$dashboard] ?? []),
            ...$widgets,
        ];
    }

    public static function isHrDashboard(?User $user): bool
    {
        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('dashboard.hr.view'));
    }

    public static function widgetsFor(?User $user): array
    {
        if (self::isHrDashboard($user)) {
            return self::hrWidgets();
        }

        return self::genericWidgets();
    }

    private static function hrWidgets(): array
    {
        return [
            HrKpiWidget::class,
            HrTodayWidget::class,
            HrEmployeeAlertsWidget::class,
            HrUpcomingEventsWidget::class,
            HrWorkflowWidget::class,
            HrBirthdaysWidget::class,
            HrQuickActionsWidget::class,
            ...(self::$registeredWidgets['hr'] ?? []),
        ];
    }

    private static function genericWidgets(): array
    {
        return [
            RussianTimeWidget::class,
            OrderSlaStatsWidget::class,
            ProductStatsWidget::class,
            SalesStatsWidget::class,
            AnalyticsPlaceholderWidget::class,
            ...(self::$registeredWidgets['generic'] ?? []),
        ];
    }
}
