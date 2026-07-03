<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use Filament\Widgets\Widget;

class HrQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.hr.quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'actions' => [
                ['label' => 'Добавить сотрудника', 'url' => UserResource::getUrl('create'), 'style' => 'primary'],
                ['label' => 'Создать отдел', 'url' => DepartmentResource::getUrl('create'), 'style' => 'gray'],
                ['label' => 'Открыть календарь', 'url' => UserResource::getUrl('index'), 'style' => 'gray'],
                ['label' => 'Создать заявку', 'url' => EmployeeScheduleRequestResource::getUrl('create'), 'style' => 'gray'],
                ['label' => 'Документы', 'url' => UserResource::getUrl('index'), 'style' => 'gray'],
            ],
        ];
    }
}
