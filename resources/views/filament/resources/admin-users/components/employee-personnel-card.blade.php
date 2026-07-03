@php
    use App\Models\User;
    use App\Support\AdminRoles;
    use Illuminate\Support\Facades\Storage;

    /** @var User|null $employee */
    $employee?->loadMissing(['department', 'roles']);

    $photoUrl = $employee?->avatar_path
        ? Storage::disk('public')->url($employee->avatar_path)
        : null;

    $employeeId = data_get($employee, 'employee_number')
        ?: data_get($employee, 'personnel_number')
        ?: 'Будет добавлен';

    $statusLabel = $employee?->getEmploymentStatusLabel() ?? 'Не указан';
    $statusColor = User::employeeStatusColor($employee?->employment_status ?? $employee?->employee_status ?? null);
    $departmentName = $employee?->department?->name ?? 'Не указан';
    $position = data_get($employee, 'position') ?: 'Не указана';
    $roleLabel = AdminRoles::primaryLabel($employee);

    $statusStyles = [
        'success' => 'background: #dcfce7; color: #166534; border-color: #bbf7d0;',
        'info' => 'background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe;',
        'warning' => 'background: #fef3c7; color: #92400e; border-color: #fde68a;',
        'danger' => 'background: #fee2e2; color: #991b1b; border-color: #fecaca;',
        'gray' => 'background: #f3f4f6; color: #374151; border-color: #e5e7eb;',
    ][$statusColor] ?? 'background: #f3f4f6; color: #374151; border-color: #e5e7eb;';
@endphp

<div style="align-items: stretch; display: flex; flex-wrap: wrap; gap: 16px; max-width: 100%;">
    <div style="background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12); height: 155px; overflow: hidden; width: 120px;">
        @if ($photoUrl)
            <img
                src="{{ $photoUrl }}"
                alt="{{ $employee?->name ? 'Фото сотрудника '.$employee->name : 'Фото сотрудника' }}"
                style="display: block; height: 155px; object-fit: cover; width: 120px;"
            >
        @else
            <div style="align-items: center; background: #f9fafb; color: #9ca3af; display: flex; font-size: 12px; font-weight: 600; height: 155px; justify-content: center; line-height: 1.3; padding: 12px; text-align: center; width: 120px;">
                Фото<br>35 × 45
            </div>
        @endif
    </div>

    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); flex: 1 1 260px; min-width: 0; padding: 14px;">
        <div style="align-items: start; display: flex; gap: 12px; justify-content: space-between;">
            <div style="min-width: 0;">
                <div style="color: #6b7280; font-size: 12px; font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase;">Личное дело</div>
                <div style="color: #111827; font-size: 16px; font-weight: 700; line-height: 1.35; margin-top: 2px; overflow-wrap: anywhere;">
                    {{ $employee?->name ?? 'Новый сотрудник' }}
                </div>
            </div>

            <div style="{{ $statusStyles }} border: 1px solid; border-radius: 999px; flex: none; font-size: 12px; font-weight: 600; line-height: 1; padding: 6px 9px; white-space: nowrap;">
                {{ $statusLabel }}
            </div>
        </div>

        <dl style="display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 14px 0 0;">
            <div>
                <dt style="color: #6b7280; font-size: 12px;">ID сотрудника</dt>
                <dd style="color: #111827; font-size: 14px; font-weight: 600; margin: 2px 0 0; overflow-wrap: anywhere;">{{ $employeeId }}</dd>
            </div>

            <div>
                <dt style="color: #6b7280; font-size: 12px;">Основная роль</dt>
                <dd style="color: #111827; font-size: 14px; font-weight: 600; margin: 2px 0 0; overflow-wrap: anywhere;">{{ $roleLabel }}</dd>
            </div>

            <div>
                <dt style="color: #6b7280; font-size: 12px;">Отдел</dt>
                <dd style="color: #111827; font-size: 14px; font-weight: 600; margin: 2px 0 0; overflow-wrap: anywhere;">{{ $departmentName }}</dd>
            </div>

            <div>
                <dt style="color: #6b7280; font-size: 12px;">Должность</dt>
                <dd style="color: #111827; font-size: 14px; font-weight: 600; margin: 2px 0 0; overflow-wrap: anywhere;">{{ $position }}</dd>
            </div>
        </dl>
    </div>
</div>
