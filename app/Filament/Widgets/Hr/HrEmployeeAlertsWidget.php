<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\User;
use Filament\Widgets\Widget;

class HrEmployeeAlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.hr.employee-alerts-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        $alerts = collect();

        User::query()
            ->with(['department', 'manager', 'activeDocuments'])
            ->whereNull('archived_at')
            ->orderBy('name')
            ->limit(80)
            ->get()
            ->each(function (User $employee) use ($alerts): void {
                $issues = [];

                if ($employee->manager_id === null) {
                    $issues[] = 'Не назначен руководитель';
                }

                if ($employee->department_id === null) {
                    $issues[] = 'Не указан отдел';
                }

                if ($employee->employment_type === null) {
                    $issues[] = 'Не указан тип трудоустройства';
                }

                if ($employee->citizenship_type === null) {
                    $issues[] = 'Не указано гражданство';
                }

                if ($employee->schedule_type === null) {
                    $issues[] = 'Не указан график';
                }

                if ($employee->missingRequiredDocuments() !== []) {
                    $issues[] = 'Не хватает документов';
                }

                if ($employee->probation_enabled && $employee->probation_ends_at?->isPast()) {
                    $issues[] = 'Нужно решение по испытательному сроку';
                }

                if ($issues === []) {
                    return;
                }

                $alerts->push([
                    'employee' => $employee->name,
                    'department' => $employee->department?->name,
                    'issues' => $issues,
                    'url' => UserResource::getUrl('view', ['record' => $employee]),
                ]);
            });

        return [
            'alerts' => $alerts->take(10)->values()->all(),
        ];
    }
}
