@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Department> $departments */
    $selectedDepartmentId = filled($selectedDepartmentId) ? (string) $selectedDepartmentId : null;

    $departmentUrl = function (?int $departmentId) use ($baseUrl): string {
        if ($departmentId === null) {
            return $baseUrl;
        }

        return $baseUrl.'?'.http_build_query([
            'tableFilters' => [
                'department_id' => [
                    'value' => $departmentId,
                ],
            ],
        ]);
    };

    $managementUrl = $baseUrl.'?'.http_build_query([
        'tableFilters' => [
            'management' => [
                'isActive' => true,
            ],
        ],
    ]);

    $chipStyle = function (bool $active): string {
        return $active
            ? 'background: #16a34a; border-color: #16a34a; color: #ffffff;'
            : 'background: #ffffff; border-color: #d1d5db; color: #374151;';
    };
@endphp

<section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 14px;">
    <div style="align-items: center; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
        <div>
            <div style="color: #111827; font-size: 15px; font-weight: 700;">Сотрудники</div>
            <div style="color: #6b7280; font-size: 12px; line-height: 1.4; margin-top: 2px;">Глобальный поиск по компании. Для ежедневной работы открывайте отдел.</div>
        </div>

        <a href="{{ \App\Filament\Resources\Departments\DepartmentResource::getUrl('index') }}" style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px; color: #374151; display: inline-flex; font-size: 13px; font-weight: 600; padding: 8px 10px; text-decoration: none;">
            Открыть отделы
        </a>
    </div>

    <div style="align-items: center; display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
        <a href="{{ $departmentUrl(null) }}" style="{{ $chipStyle($selectedDepartmentId === null && ! $managementSelected) }} border: 1px solid; border-radius: 999px; display: inline-flex; font-size: 13px; font-weight: 600; line-height: 1; padding: 8px 11px; text-decoration: none;">
            Все
        </a>

        <a href="{{ $managementUrl }}" style="{{ $chipStyle($managementSelected) }} border: 1px solid; border-radius: 999px; display: inline-flex; font-size: 13px; font-weight: 600; line-height: 1; padding: 8px 11px; text-decoration: none;">
            Руководящий состав
        </a>

        @foreach ($departments as $department)
            <a href="{{ $departmentUrl($department->id) }}" style="{{ $chipStyle($selectedDepartmentId === (string) $department->id) }} border: 1px solid; border-radius: 999px; display: inline-flex; font-size: 13px; font-weight: 600; line-height: 1; max-width: 100%; overflow-wrap: anywhere; padding: 8px 11px; text-decoration: none;">
                {{ $department->name }}
            </a>
        @endforeach
    </div>
</section>
