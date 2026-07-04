<?php

namespace App\Filament\Widgets\Employee;

use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeAttentionWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.attention-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        /** @var User $employee */
        $employee = auth()->user();
        $items = collect();

        $employee->expiringDocuments()
            ->take(3)
            ->each(fn (EmployeeDocument $document) => $items->push($document->isExpired()
                ? 'Документ просрочен: '.$document->getCategoryLabel()
                : 'Документ скоро истекает: '.$document->getCategoryLabel()));

        $returnedRequests = EmployeeScheduleRequest::query()
            ->where('employee_id', $employee->getKey())
            ->where('status', EmployeeScheduleRequest::STATUS_RETURNED)
            ->whereNull('deleted_at')
            ->count();

        if ($returnedRequests > 0) {
            $items->push('Есть возвращённые заявки: '.$returnedRequests);
        }

        if ($employee->probation_enabled && $employee->probation_ends_at?->betweenIncluded(today(), today()->addDays(14))) {
            $items->push('Испытательный срок заканчивается '.$employee->probation_ends_at->format('d.m.Y'));
        }

        return [
            'items' => $items->take(6)->values()->all(),
        ];
    }
}
