<?php

namespace App\Filament\Widgets\Employee;

use App\Filament\Pages\MyCalendar;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        return [
            'actions' => [
                ['label' => 'Создать заявку', 'url' => EmployeeScheduleRequestResource::getUrl('create'), 'primary' => true],
                ['label' => 'Открыть календарь', 'url' => MyCalendar::getUrl(), 'primary' => false],
                ['label' => 'Открыть документы', 'url' => '#employee-documents', 'primary' => false],
                ['label' => 'Написать сообщение', 'url' => '#employee-messages', 'primary' => false],
            ],
        ];
    }
}
