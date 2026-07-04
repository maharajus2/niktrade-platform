<?php

namespace App\Filament\Widgets\Employee;

use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeTasksWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.tasks-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }
}
