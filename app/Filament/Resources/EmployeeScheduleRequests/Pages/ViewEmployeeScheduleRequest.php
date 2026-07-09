<?php

namespace App\Filament\Resources\EmployeeScheduleRequests\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeScheduleRequest;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ViewEmployeeScheduleRequest extends ViewRecord
{
    protected static string $resource = EmployeeScheduleRequestResource::class;

    protected string $view = 'filament.resources.employee-schedule-requests.pages.view-request';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string|Htmlable
    {
        return $this->record instanceof EmployeeScheduleRequest
            ? $this->record->getTypeLabel()
            : 'Заявка';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $this->record->loadMissing([
            'employee.department',
            'employee.manager',
            'requestedBy',
            'reviewedBy',
            'approvalWorkflow.currentApprover',
            'approvalWorkflow.events.actor',
            'approvalWorkflow.events.forwardedTo',
        ]);

        return [
            'request' => $this->record,
        ];
    }
}
