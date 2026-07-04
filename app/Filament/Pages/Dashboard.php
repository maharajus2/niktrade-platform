<?php

namespace App\Filament\Pages;

use App\Support\Dashboard\DashboardWidgetRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Главная';

    protected static ?string $navigationLabel = 'Главная';

    public static function getNavigationLabel(): string
    {
        return DashboardWidgetRegistry::canChooseDashboard(auth()->user())
            || DashboardWidgetRegistry::dashboardKeyFor(auth()->user()) !== DashboardWidgetRegistry::DASHBOARD_GENERIC
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
        return DashboardWidgetRegistry::canChooseDashboard(auth()->user())
            || DashboardWidgetRegistry::dashboardKeyFor(auth()->user()) !== DashboardWidgetRegistry::DASHBOARD_GENERIC
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('chooseDashboard')
                ->label('Выбрать рабочий стол')
                ->icon('heroicon-o-squares-2x2')
                ->visible(fn (): bool => DashboardWidgetRegistry::canChooseDashboard(auth()->user()))
                ->form([
                    Select::make('dashboard_preference')
                        ->label('Рабочий стол')
                        ->options(fn (): array => DashboardWidgetRegistry::availableDashboardsFor(auth()->user()))
                        ->default(fn (): string => DashboardWidgetRegistry::dashboardKeyFor(auth()->user()))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    auth()->user()?->update([
                        'dashboard_preference' => $data['dashboard_preference'],
                    ]);

                    Notification::make()
                        ->title('Рабочий стол обновлён.')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function getWidgets(): array
    {
        return DashboardWidgetRegistry::widgetsFor(auth()->user());
    }
}
