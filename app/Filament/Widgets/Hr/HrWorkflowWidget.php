<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Models\EmployeeScheduleRequest;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class HrWorkflowWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.workflow-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        $activeStatuses = [
            EmployeeScheduleRequest::STATUS_PENDING,
            EmployeeScheduleRequest::STATUS_IN_REVIEW,
            EmployeeScheduleRequest::STATUS_FORWARDED,
        ];

        return [
            'url' => EmployeeScheduleRequestResource::getUrl('index'),
            'items' => [
                [
                    'label' => 'Ожидают HR',
                    'value' => EmployeeScheduleRequest::query()
                        ->whereNull('deleted_at')
                        ->whereIn('status', $activeStatuses)
                        ->whereHas('approvalWorkflow.currentApprover.roles', fn (Builder $query): Builder => $query->where('name', 'hr'))
                        ->count(),
                    'color' => 'warning',
                ],
                [
                    'label' => 'Переданы HR',
                    'value' => EmployeeScheduleRequest::query()
                        ->whereNull('deleted_at')
                        ->where('status', EmployeeScheduleRequest::STATUS_FORWARDED)
                        ->count(),
                    'color' => 'info',
                ],
                [
                    'label' => 'Возвращены HR',
                    'value' => EmployeeScheduleRequest::query()
                        ->whereNull('deleted_at')
                        ->where('status', EmployeeScheduleRequest::STATUS_RETURNED)
                        ->whereHas('approvalWorkflow.events.actor.roles', fn (Builder $query): Builder => $query->where('name', 'hr'))
                        ->count(),
                    'color' => 'gray',
                ],
                [
                    'label' => 'Одобрено сегодня',
                    'value' => EmployeeScheduleRequest::query()
                        ->whereDate('reviewed_at', today())
                        ->where('status', EmployeeScheduleRequest::STATUS_APPROVED)
                        ->count(),
                    'color' => 'success',
                ],
                [
                    'label' => 'Отклонено сегодня',
                    'value' => EmployeeScheduleRequest::query()
                        ->whereDate('reviewed_at', today())
                        ->where('status', EmployeeScheduleRequest::STATUS_REJECTED)
                        ->count(),
                    'color' => 'danger',
                ],
            ],
        ];
    }
}
