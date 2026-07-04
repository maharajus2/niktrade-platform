<?php

namespace App\Filament\Widgets\Employee;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeRequestsWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.requests-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        $employeeId = auth()->id();

        return [
            'indexUrl' => EmployeeScheduleRequestResource::getUrl('index'),
            'createUrl' => EmployeeScheduleRequestResource::getUrl('create'),
            'counts' => [
                'pending' => EmployeeScheduleRequest::query()
                    ->where('employee_id', $employeeId)
                    ->whereIn('status', [
                        EmployeeScheduleRequest::STATUS_PENDING,
                        EmployeeScheduleRequest::STATUS_IN_REVIEW,
                        EmployeeScheduleRequest::STATUS_FORWARDED,
                    ])
                    ->whereNull('deleted_at')
                    ->count(),
                'approved' => EmployeeScheduleRequest::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', EmployeeScheduleRequest::STATUS_APPROVED)
                    ->whereNull('deleted_at')
                    ->count(),
                'rejected' => EmployeeScheduleRequest::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', EmployeeScheduleRequest::STATUS_REJECTED)
                    ->whereNull('deleted_at')
                    ->count(),
                'returned' => EmployeeScheduleRequest::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', EmployeeScheduleRequest::STATUS_RETURNED)
                    ->whereNull('deleted_at')
                    ->count(),
            ],
        ];
    }
}
