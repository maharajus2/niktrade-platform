@php
    use App\Filament\Resources\AdminUsers\UserResource;
    use App\Models\User;

    /** @var \App\Models\Department $department */
    $department->loadMissing(['manager', 'actingManager', 'employees']);

    $employees = $department->employees;
    $employeesCount = $employees->count();
    $workingCount = $employees
        ->filter(fn (User $employee): bool => ($employee->employment_status ?? $employee->employee_status) === User::STATUS_WORKING)
        ->count();
    $vacationCount = $employees
        ->filter(fn (User $employee): bool => ($employee->employment_status ?? $employee->employee_status) === User::STATUS_VACATION)
        ->count();
    $sickLeaveCount = $employees
        ->filter(fn (User $employee): bool => ($employee->employment_status ?? $employee->employee_status) === User::STATUS_SICK_LEAVE)
        ->count();
    $probationCount = $employees
        ->filter(fn (User $employee): bool => $employee->isOnProbation())
        ->count();
    $documentsAttentionCount = $employees
        ->filter(fn (User $employee): bool => UserResource::documentStatusLabel($employee) !== 'Документы ОК')
        ->count();

    $averageTenure = $employees
        ->filter(fn (User $employee): bool => filled($employee->hire_date))
        ->map(fn (User $employee): int => $employee->hire_date->diffInMonths($employee->dismissal_date ?: now()))
        ->avg();

    $averageTenureLabel = $averageTenure === null
        ? 'Будет рассчитан'
        : ((int) floor($averageTenure / 12)).' г. '.((int) $averageTenure % 12).' мес.';

    $stats = [
        ['label' => 'Работают', 'value' => $workingCount, 'color' => '#15803d', 'background' => '#f0fdf4'],
        ['label' => 'Сегодня выходной', 'value' => '—', 'color' => '#374151', 'background' => '#f9fafb'],
        ['label' => 'Отпуск', 'value' => $vacationCount, 'color' => '#6d28d9', 'background' => '#f5f3ff'],
        ['label' => 'Больничный', 'value' => $sickLeaveCount, 'color' => '#b45309', 'background' => '#fffbeb'],
        ['label' => 'Испытательный срок', 'value' => $probationCount, 'color' => '#0f766e', 'background' => '#f0fdfa'],
        ['label' => 'Документы требуют внимания', 'value' => $documentsAttentionCount, 'color' => $documentsAttentionCount > 0 ? '#b91c1c' : '#374151', 'background' => $documentsAttentionCount > 0 ? '#fef2f2' : '#f9fafb'],
    ];
@endphp

<div style="display: grid; gap: 16px; max-width: 100%;">
    <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 16px;">
        <div style="align-items: flex-start; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between;">
            <div style="min-width: 0;">
                <div style="color: #6b7280; font-size: 12px; font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase;">Отдел</div>
                <h2 style="color: #111827; font-size: 22px; font-weight: 700; line-height: 1.25; margin: 4px 0 0; overflow-wrap: anywhere;">{{ $department->name }}</h2>
                @if ($department->description)
                    <p style="color: #4b5563; font-size: 14px; line-height: 1.5; margin: 8px 0 0; max-width: 760px;">{{ $department->description }}</p>
                @endif
            </div>

            <div style="display: grid; gap: 8px; min-width: min(100%, 280px);">
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px;">
                    <div style="color: #6b7280; font-size: 12px;">Руководитель</div>
                    <div style="color: #111827; font-size: 14px; font-weight: 600; margin-top: 2px;">{{ $department->manager?->name ?? 'Не назначен' }}</div>
                </div>

                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px;">
                    <div style="color: #6b7280; font-size: 12px;">Заместитель</div>
                    <div style="color: #111827; font-size: 14px; font-weight: 600; margin-top: 2px;">{{ $department->actingManager?->name ?? 'Будет добавлен' }}</div>
                </div>
            </div>
        </div>

        <div style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-top: 16px;">
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px;">
                <div style="color: #6b7280; font-size: 12px;">Всего сотрудников</div>
                <div style="color: #111827; font-size: 24px; font-weight: 700; line-height: 1.2; margin-top: 4px;">{{ $employeesCount }}</div>
            </div>

            @foreach ($stats as $stat)
                <div style="background: {{ $stat['background'] }}; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px;">
                    <div style="color: #6b7280; font-size: 12px;">{{ $stat['label'] }}</div>
                    <div style="color: {{ $stat['color'] }}; font-size: 24px; font-weight: 700; line-height: 1.2; margin-top: 4px;">{{ $stat['value'] }}</div>
                </div>
            @endforeach

            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px;">
                <div style="color: #6b7280; font-size: 12px;">Средний стаж</div>
                <div style="color: #111827; font-size: 16px; font-weight: 700; line-height: 1.2; margin-top: 8px;">{{ $averageTenureLabel }}</div>
            </div>
        </div>
    </section>
</div>
