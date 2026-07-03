<?php

namespace App\Filament\Resources\EmployeeScheduleRequests\Pages;

use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeScheduleRequest;
use Filament\Actions\Action;
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
            Action::make('activeRequests')
                ->label('Активные')
                ->icon('heroicon-o-inbox')
                ->color(fn (): string => $this->getLifecycleFilter() === EmployeeScheduleRequest::LIFECYCLE_ACTIVE ? 'primary' : 'gray')
                ->action(fn (): null => $this->switchLifecycle(EmployeeScheduleRequest::LIFECYCLE_ACTIVE)),

            Action::make('archivedRequests')
                ->label('Архив')
                ->icon('heroicon-o-archive-box')
                ->color(fn (): string => $this->getLifecycleFilter() === EmployeeScheduleRequest::LIFECYCLE_ARCHIVE ? 'primary' : 'gray')
                ->action(fn (): null => $this->switchLifecycle(EmployeeScheduleRequest::LIFECYCLE_ARCHIVE)),

            Action::make('deletedRequests')
                ->label('Удалённые')
                ->icon('heroicon-o-trash')
                ->color(fn (): string => $this->getLifecycleFilter() === EmployeeScheduleRequest::LIFECYCLE_DELETED ? 'danger' : 'gray')
                ->visible(fn (): bool => EmployeeScheduleRequestResource::canViewDeletedRequests())
                ->action(fn (): null => $this->switchLifecycle(EmployeeScheduleRequest::LIFECYCLE_DELETED)),

            CreateAction::make()
                ->label('Создать заявку'),
        ];
    }

    public function switchLifecycle(string $lifecycle): null
    {
        if ($lifecycle === EmployeeScheduleRequest::LIFECYCLE_DELETED && ! EmployeeScheduleRequestResource::canViewDeletedRequests()) {
            return null;
        }

        $this->tableFilters ??= [];
        $this->tableFilters['lifecycle']['value'] = $lifecycle;

        $this->getTableFiltersForm()->fill($this->tableFilters);
        $this->handleTableFilterUpdates();

        return null;
    }

    private function getLifecycleFilter(): string
    {
        return $this->tableFilters['lifecycle']['value'] ?? EmployeeScheduleRequest::LIFECYCLE_ACTIVE;
    }
}
