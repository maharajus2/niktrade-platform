<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\DashboardWidgetRegistry;
use Filament\Widgets\Widget;

class WorkplacePlaceholderWidget extends Widget
{
    protected string $view = 'filament.widgets.workplace-placeholder-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'contextLabel' => DashboardWidgetRegistry::contextLabelFor(auth()->user()),
        ];
    }
}
