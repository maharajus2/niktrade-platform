<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AnalyticsPlaceholderWidget extends Widget
{
    protected static string $view = 'filament.widgets.analytics-placeholder-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];
}
