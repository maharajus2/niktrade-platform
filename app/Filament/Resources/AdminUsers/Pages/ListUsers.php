<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\Department;
use App\Models\EmployeeScheduleEntry;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.admin-users.pages.employee-list';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Сотрудники';

    protected static ?string $breadcrumb = 'Сотрудники';

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
            'employeesPage' => $this->employeesPageData(),
        ];
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

    private function employeesPageData(): array
    {
        $today = today();
        $tab = request()->query('tab', 'all');
        $view = request()->query('view', 'table');
        $queryText = trim((string) request()->query('q', ''));

        $employeesQuery = $this->employeesQuery()
            ->when($queryText !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($queryText): void {
                $query
                    ->where('name', 'ilike', "%{$queryText}%")
                    ->orWhere('email', 'ilike', "%{$queryText}%")
                    ->orWhere('phone', 'ilike', "%{$queryText}%")
                    ->orWhere('position', 'ilike', "%{$queryText}%");
            }))
            ->when(request()->filled('department'), fn (Builder $query): Builder => $query->where('department_id', request()->integer('department')))
            ->when(request()->filled('schedule'), fn (Builder $query): Builder => $query->where('schedule_type', request()->query('schedule')))
            ->when(request()->filled('status'), fn (Builder $query): Builder => $query->where('employment_status', request()->query('status')))
            ->when(request()->filled('employment_type'), fn (Builder $query): Builder => $query->where('employment_type', request()->query('employment_type')))
            ->when(request()->filled('role'), fn (Builder $query): Builder => $query->whereHas('roles', fn (Builder $query): Builder => $query->where('name', request()->query('role'))));

        $employeesQuery = $this->scopeTab($employeesQuery, $tab);

        $employees = $employeesQuery
            ->orderByRaw('archived_at asc nulls first')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::query()
            ->withCount(['employees as active_employees_count' => fn (Builder $query): Builder => $this->scopeActive($query)])
            ->orderByDesc('active_employees_count')
            ->orderBy('name')
            ->get();

        $birthdays = User::query()
            ->with('department')
            ->whereNotNull('date_of_birth')
            ->whereNull('archived_at')
            ->get()
            ->map(fn (User $employee): array => [
                'employee' => $employee,
                'date' => $this->nextBirthday($employee),
            ])
            ->sortBy('date')
            ->take(5)
            ->values();

        $tabs = [
            'all' => ['label' => 'Все сотрудники', 'count' => $this->employeesQuery()->count()],
            'active' => ['label' => 'Активные', 'count' => $this->scopeActive($this->employeesQuery())->count()],
            'vacation' => ['label' => 'В отпуске', 'count' => $this->employeesQuery()->where('employment_status', User::STATUS_VACATION)->count()],
            'sick' => ['label' => 'На больничном', 'count' => $this->employeesQuery()->where('employment_status', User::STATUS_SICK_LEAVE)->count()],
            'dismissed' => ['label' => 'Уволенные', 'count' => $this->employeesQuery()->where('employment_status', User::STATUS_DISMISSED)->count()],
            'archived' => ['label' => 'Архив', 'count' => $this->employeesQuery()->whereNotNull('archived_at')->count()],
        ];

        return [
            'employees' => $employees,
            'departments' => $departments,
            'roles' => Role::query()
                ->orderBy('name')
                ->pluck('name')
                ->mapWithKeys(fn (string $role): array => [$role => AdminRoles::label($role)])
                ->all(),
            'tabs' => $tabs,
            'activeTab' => array_key_exists($tab, $tabs) ? $tab : 'all',
            'view' => in_array($view, ['table', 'cards'], true) ? $view : 'table',
            'query' => [
                'q' => $queryText,
                'department' => request()->query('department'),
                'schedule' => request()->query('schedule'),
                'status' => request()->query('status'),
                'employment_type' => request()->query('employment_type'),
                'role' => request()->query('role'),
            ],
            'kpis' => [
                ['label' => 'Всего сотрудников', 'value' => $this->employeesQuery()->count(), 'delta' => '+'.$this->employeesQuery()->whereDate('created_at', '>=', $today->copy()->startOfMonth())->count().' за месяц', 'tone' => 'blue', 'icon' => 'users'],
                ['label' => 'Новые сотрудники', 'value' => $this->employeesQuery()->whereDate('created_at', '>=', $today->copy()->startOfMonth())->count(), 'delta' => '+'.$this->employeesQuery()->whereDate('created_at', '>=', $today->copy()->subWeek())->count().' за неделю', 'tone' => 'cyan', 'icon' => 'plus'],
                ['label' => 'В отпуске', 'value' => $this->employeesQuery()->where('employment_status', User::STATUS_VACATION)->count(), 'delta' => '+'.$this->todayEntryCount(EmployeeScheduleEntry::TYPE_VACATION).' сегодня', 'tone' => 'violet', 'icon' => 'calendar'],
                ['label' => 'На больничном', 'value' => $this->employeesQuery()->where('employment_status', User::STATUS_SICK_LEAVE)->count(), 'delta' => '+'.$this->todayEntryCount(EmployeeScheduleEntry::TYPE_SICK_LEAVE).' сегодня', 'tone' => 'red', 'icon' => 'alert'],
                ['label' => 'Требуют оформления', 'value' => $this->employeesMissingDocumentsCount(), 'delta' => 'документы', 'tone' => 'amber', 'icon' => 'file'],
            ],
            'departmentStats' => $departments->take(6)->map(fn (Department $department): array => [
                'department' => $department,
                'count' => $department->active_employees_count,
                'percent' => max(0, $this->scopeActive($this->employeesQuery())->count()) > 0
                    ? round(($department->active_employees_count / $this->scopeActive($this->employeesQuery())->count()) * 100)
                    : 0,
            ])->values(),
            'birthdays' => $birthdays,
            'urls' => [
                'index' => UserResource::getUrl('index'),
                'create' => UserResource::getUrl('create'),
                'departments' => DepartmentResource::getUrl('index'),
                'createRequest' => EmployeeScheduleRequestResource::getUrl('create'),
            ],
            'canCreate' => UserResource::canCreate(),
        ];
    }

    private function employeesQuery(): Builder
    {
        return User::query()
            ->with(['department', 'roles'])
            ->withCount('activeDocuments');
    }

    private function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('employment_status')
                ->orWhere('employment_status', User::STATUS_WORKING));
    }

    private function scopeTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'active' => $this->scopeActive($query),
            'vacation' => $query->where('employment_status', User::STATUS_VACATION),
            'sick' => $query->where('employment_status', User::STATUS_SICK_LEAVE),
            'dismissed' => $query->where('employment_status', User::STATUS_DISMISSED),
            'archived' => $query->whereNotNull('archived_at'),
            default => $query,
        };
    }

    private function todayEntryCount(string $type): int
    {
        return EmployeeScheduleEntry::query()
            ->hrVisible()
            ->whereDate('date', today())
            ->where('type', $type)
            ->distinct('employee_id')
            ->count('employee_id');
    }

    private function employeesMissingDocumentsCount(): int
    {
        return User::query()
            ->whereNull('archived_at')
            ->with('activeDocuments')
            ->get()
            ->filter(fn (User $employee): bool => $employee->missingRequiredDocuments() !== [])
            ->count();
    }

    private function nextBirthday(User $employee): Carbon
    {
        $birthday = Carbon::parse($employee->date_of_birth)->year((int) today()->format('Y'));

        return $birthday->isBefore(today()) ? $birthday->addYear() : $birthday;
    }
}
