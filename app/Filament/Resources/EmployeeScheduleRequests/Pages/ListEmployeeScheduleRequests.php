<?php

namespace App\Filament\Resources\EmployeeScheduleRequests\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeScheduleRequests extends ListRecords
{
    protected static string $resource = EmployeeScheduleRequestResource::class;

    public function getTitle(): string
    {
        return EmployeeScheduleRequestResource::canReviewAny() ? 'Заявки сотрудников' : 'Мои заявки';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать заявку'),
        ];
    }
}
