<?php

namespace App\Filament\Pages;

use App\Support\Dashboard\DashboardWidgetRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Workplace extends BaseDashboard
{
    protected static string $routePath = '/workplace';

    protected static ?string $title = 'Рабочее пространство';

    protected static ?string $navigationLabel = 'Рабочее пространство';

    protected static ?int $navigationSort = -1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

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
        return 'Рабочее пространство';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (DashboardWidgetRegistry::contextKeyFor(auth()->user()) === DashboardWidgetRegistry::CONTEXT_EMPLOYEE) {
            return 'Добро пожаловать, '.auth()->user()->name.' · '.now()->translatedFormat('d F Y');
        }

        return 'Контекст: '.DashboardWidgetRegistry::contextLabelFor(auth()->user());
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
        return DashboardWidgetRegistry::workspaceWidgetsFor(auth()->user());
    }
}
