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
    protected static string $routePath = '/dashboard';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Главная';

    protected static ?string $navigationLabel = 'Главная';

    public function getColumns(): array|int
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function mount(): void
    {
        $this->redirect(Workplace::getUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Главная';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Контекст: '.DashboardWidgetRegistry::contextLabelFor(auth()->user()).' · '.now()->translatedFormat('d F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('chooseDashboard')
                ->label('Выбрать контекст')
                ->icon('heroicon-o-squares-2x2')
                ->visible(fn (): bool => DashboardWidgetRegistry::canChooseDashboard(auth()->user()))
                ->form([
                    Select::make('dashboard_preference')
                        ->label('Рабочий контекст')
                        ->options(fn (): array => DashboardWidgetRegistry::availableContextsFor(auth()->user()))
                        ->default(fn (): string => DashboardWidgetRegistry::contextKeyFor(auth()->user()))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    auth()->user()?->update([
                        'dashboard_preference' => $data['dashboard_preference'],
                    ]);

                    Notification::make()
                        ->title('Рабочий контекст обновлён.')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function getWidgets(): array
    {
        return DashboardWidgetRegistry::homeWidgetsFor(auth()->user());
    }
}
