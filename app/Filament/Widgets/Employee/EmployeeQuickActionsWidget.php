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
                ['label' => 'Создать заявку', 'url' => EmployeeScheduleRequestResource::getUrl('create'), 'primary' => true, 'disabled' => false],
                ['label' => 'Открыть календарь', 'url' => MyCalendar::getUrl(), 'primary' => false, 'disabled' => false],
                ['label' => 'Открыть документы', 'url' => '#employee-documents', 'primary' => false, 'disabled' => false],
                ['label' => 'Создать задачу', 'url' => null, 'primary' => false, 'disabled' => true],
                ['label' => 'Написать сообщение', 'url' => null, 'primary' => false, 'disabled' => true],
            ],
        ];
    }
}
