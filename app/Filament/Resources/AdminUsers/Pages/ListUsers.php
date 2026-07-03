<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\Department;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Сотрудники';

    protected static ?string $breadcrumb = 'Сотрудники';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => UserResource::canCreate()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.resources.admin-users.components.employee-department-navigation')
                    ->viewData(fn (): array => $this->departmentNavigationData())
                    ->columnSpanFull(),

                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function filterByDepartment(?int $departmentId = null): void
    {
        $this->tableFilters ??= [];
        $this->tableFilters['department_id']['value'] = $departmentId;
        $this->tableFilters['management']['isActive'] = false;

        $this->getTableFiltersForm()->fill($this->tableFilters);
        $this->handleTableFilterUpdates();
    }

    public function filterManagement(): void
    {
        $this->tableFilters ??= [];
        $this->tableFilters['department_id']['value'] = null;
        $this->tableFilters['management']['isActive'] = true;

        $this->getTableFiltersForm()->fill($this->tableFilters);
        $this->handleTableFilterUpdates();
    }

    private function departmentNavigationData(): array
    {
        $selectedDepartmentId = $this->tableFilters['department_id']['value'] ?? null;
        $selectedDepartmentId = filled($selectedDepartmentId) ? (int) $selectedDepartmentId : null;

        $departments = Department::query()
            ->where('is_active', true)
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name']);

        if ($selectedDepartmentId !== null && ! $departments->contains('id', $selectedDepartmentId)) {
            $selectedDepartment = Department::query()
                ->whereKey($selectedDepartmentId)
                ->first(['id', 'name']);

            if ($selectedDepartment !== null) {
                $departments->push($selectedDepartment);
            }
        }

        return [
            'departments' => $departments,
            'selectedDepartmentId' => $selectedDepartmentId,
            'managementSelected' => (bool) ($this->tableFilters['management']['isActive'] ?? false),
        ];
    }
}
