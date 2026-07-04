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
    public const DASHBOARD_GENERIC = 'generic';

    public const DASHBOARD_HR = 'hr';

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
        return self::dashboardKeyFor($user) === self::DASHBOARD_HR;
    }

    public static function widgetsFor(?User $user): array
    {
        return match (self::dashboardKeyFor($user)) {
            self::DASHBOARD_HR => self::hrWidgets(),
            default => self::genericWidgets(),
        };
    }

    public static function dashboardKeyFor(?User $user): string
    {
        if (! $user instanceof User) {
            return self::DASHBOARD_GENERIC;
        }

        $available = self::availableDashboardsFor($user);

        if ($user->dashboard_preference && array_key_exists($user->dashboard_preference, $available)) {
            return $user->dashboard_preference;
        }

        if (array_key_exists(self::DASHBOARD_HR, $available)) {
            return self::DASHBOARD_HR;
        }

        return self::DASHBOARD_GENERIC;
    }

    public static function canChooseDashboard(?User $user): bool
    {
        return $user instanceof User && $user->hasRole('super_admin');
    }

    public static function availableDashboardsFor(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                self::DASHBOARD_GENERIC => 'Общий',
            ];
        }

        $dashboards = [
            self::DASHBOARD_GENERIC => 'Общий',
        ];

        if ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('dashboard.hr.view')) {
            $dashboards[self::DASHBOARD_HR] = 'HR';
        }

        return $dashboards;
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
