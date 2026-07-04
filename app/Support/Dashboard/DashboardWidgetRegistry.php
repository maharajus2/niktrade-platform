<?php

namespace App\Support\Dashboard;

use App\Filament\Widgets\AnalyticsPlaceholderWidget;
use App\Filament\Widgets\Employee\EmployeeAttentionWidget;
use App\Filament\Widgets\Employee\EmployeeCalendarWidget;
use App\Filament\Widgets\Employee\EmployeeDocumentsWidget;
use App\Filament\Widgets\Employee\EmployeeMessagesWidget;
use App\Filament\Widgets\Employee\EmployeeMyDayWidget;
use App\Filament\Widgets\Employee\EmployeeQuickActionsWidget;
use App\Filament\Widgets\Employee\EmployeeRequestsWidget;
use App\Filament\Widgets\Employee\EmployeeTasksWidget;
use App\Filament\Widgets\Hr\HrEmployeeAlertsWidget;
use App\Filament\Widgets\Hr\HrHomeOverviewWidget;
use App\Filament\Widgets\Hr\HrQuickActionsWidget;
use App\Filament\Widgets\Hr\HrTodayWidget;
use App\Filament\Widgets\Hr\HrUpcomingEventsWidget;
use App\Filament\Widgets\Hr\HrWorkflowWidget;
use App\Filament\Widgets\OrderSlaStatsWidget;
use App\Filament\Widgets\ProductStatsWidget;
use App\Filament\Widgets\RussianTimeWidget;
use App\Filament\Widgets\SalesStatsWidget;
use App\Filament\Widgets\WorkplacePlaceholderWidget;
use App\Models\User;

class DashboardWidgetRegistry
{
    public const CONTEXT_GENERIC = 'generic';

    public const CONTEXT_EMPLOYEE = 'employee';

    public const CONTEXT_HR = 'hr';

    public const CONTEXT_ORDERS = 'orders_manager';

    public const DASHBOARD_GENERIC = self::CONTEXT_GENERIC;

    public const DASHBOARD_HR = self::CONTEXT_HR;

    /** @var array<string, array<int, class-string>> */
    private static array $registeredWidgets = [];

    /**
     * @param  array<int, class-string>  $widgets
     */
    public static function register(string $area, array $widgets): void
    {
        self::$registeredWidgets[$area] = [
            ...(self::$registeredWidgets[$area] ?? []),
            ...$widgets,
        ];
    }

    public static function isHrDashboard(?User $user): bool
    {
        return self::contextKeyFor($user) === self::CONTEXT_HR;
    }

    public static function widgetsFor(?User $user): array
    {
        return self::homeWidgetsFor($user);
    }

    public static function homeWidgetsFor(?User $user): array
    {
        return match (self::contextKeyFor($user)) {
            self::CONTEXT_HR => self::hrHomeWidgets(),
            default => self::genericHomeWidgets(),
        };
    }

    public static function workspaceWidgetsFor(?User $user): array
    {
        return match (self::contextKeyFor($user)) {
            self::CONTEXT_HR => self::hrWorkspaceWidgets(),
            self::CONTEXT_EMPLOYEE => self::employeeWorkspaceWidgets(),
            default => [
                WorkplacePlaceholderWidget::class,
            ],
        };
    }

    public static function dashboardKeyFor(?User $user): string
    {
        return self::contextKeyFor($user);
    }

    public static function contextKeyFor(?User $user): string
    {
        if (! $user instanceof User) {
            return self::CONTEXT_GENERIC;
        }

        $available = self::availableContextsFor($user);

        if ($user->dashboard_preference && array_key_exists($user->dashboard_preference, $available)) {
            return $user->dashboard_preference;
        }

        if (array_key_exists(self::CONTEXT_HR, $available) && ! $user->hasRole('super_admin')) {
            return self::CONTEXT_HR;
        }

        return self::CONTEXT_EMPLOYEE;
    }

    public static function canChooseDashboard(?User $user): bool
    {
        return $user instanceof User && $user->hasRole('super_admin');
    }

    public static function availableDashboardsFor(?User $user): array
    {
        return self::availableContextsFor($user);
    }

    public static function availableContextsFor(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                self::CONTEXT_GENERIC => 'Общий',
            ];
        }

        $contexts = [
            self::CONTEXT_EMPLOYEE => 'Сотрудник',
        ];

        if ($user->hasRole('super_admin')) {
            $contexts[self::CONTEXT_GENERIC] = 'Общий';
        }

        if ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('dashboard.hr.view')) {
            $contexts[self::CONTEXT_HR] = 'HR';
        }

        if ($user->hasRole('super_admin')) {
            $contexts[self::CONTEXT_ORDERS] = 'Менеджер заказов';
        }

        return $contexts;
    }

    public static function contextLabelFor(?User $user): string
    {
        return self::availableContextsFor($user)[self::contextKeyFor($user)] ?? 'Сотрудник';
    }

    private static function hrHomeWidgets(): array
    {
        return [
            HrHomeOverviewWidget::class,
            ...(self::$registeredWidgets['hr.home'] ?? []),
        ];
    }

    private static function hrWorkspaceWidgets(): array
    {
        return [
            HrTodayWidget::class,
            HrEmployeeAlertsWidget::class,
            HrUpcomingEventsWidget::class,
            HrWorkflowWidget::class,
            HrQuickActionsWidget::class,
            ...(self::$registeredWidgets['hr.workspace'] ?? []),
        ];
    }

    private static function employeeWorkspaceWidgets(): array
    {
        return [
            EmployeeMyDayWidget::class,
            EmployeeCalendarWidget::class,
            EmployeeAttentionWidget::class,
            EmployeeRequestsWidget::class,
            EmployeeDocumentsWidget::class,
            EmployeeTasksWidget::class,
            EmployeeMessagesWidget::class,
            EmployeeQuickActionsWidget::class,
            ...(self::$registeredWidgets['employee.workspace'] ?? []),
        ];
    }

    private static function genericHomeWidgets(): array
    {
        return [
            RussianTimeWidget::class,
            OrderSlaStatsWidget::class,
            ProductStatsWidget::class,
            SalesStatsWidget::class,
            AnalyticsPlaceholderWidget::class,
            ...(self::$registeredWidgets['generic.home'] ?? []),
        ];
    }
}
