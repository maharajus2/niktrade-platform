<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\Department;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected string $view = 'filament.resources.departments.pages.department-list';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getViewData(): array
    {
        return [
            'departmentsPage' => $this->departmentsPageData(),
        ];
    }

    private function departmentsPageData(): array
    {
        $view = request()->query('view', 'cards');
        $queryText = trim((string) request()->query('q', ''));

        $departmentsQuery = Department::query()
            ->with(['manager', 'actingManager', 'parent'])
            ->withCount('employees')
            ->when($queryText !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($queryText): void {
                $query
                    ->where('name', 'ilike', "%{$queryText}%")
                    ->orWhere('code', 'ilike', "%{$queryText}%");
            }))
            ->when(request()->query('active') === '1', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(request()->query('without_manager') === '1', fn (Builder $query): Builder => $query->whereNull('manager_id')->whereNull('acting_manager_id'));

        $departments = $departmentsQuery
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $allDepartments = Department::query()
            ->with(['manager', 'actingManager', 'parent'])
            ->withCount('employees')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'departments' => $departments,
            'allDepartments' => $allDepartments,
            'view' => in_array($view, ['cards', 'list', 'structure'], true) ? $view : 'cards',
            'query' => [
                'q' => $queryText,
                'active' => request()->query('active'),
                'without_manager' => request()->query('without_manager'),
            ],
            'kpis' => [
                ['label' => 'Всего отделов', 'value' => $allDepartments->count(), 'tone' => 'blue', 'icon' => 'building'],
                ['label' => 'Активные', 'value' => $allDepartments->where('is_active', true)->count(), 'tone' => 'green', 'icon' => 'check-circle'],
                ['label' => 'Без руководителя', 'value' => $allDepartments->filter(fn (Department $department): bool => ! $department->getActiveHead())->count(), 'tone' => 'amber', 'icon' => 'alert'],
                ['label' => 'Сотрудников всего', 'value' => $allDepartments->sum('employees_count'), 'tone' => 'cyan', 'icon' => 'users'],
                ['label' => 'Есть вакансии', 'value' => 0, 'tone' => 'rose', 'icon' => 'plus', 'soon' => true],
            ],
            'urls' => [
                'index' => DepartmentResource::getUrl('index'),
                'create' => DepartmentResource::getUrl('create'),
                'employees' => UserResource::getUrl('index'),
            ],
            'canCreate' => DepartmentResource::canCreate(),
        ];
    }
}
