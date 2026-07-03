<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
use App\Models\EmployeeDocument;
use App\Models\EmployeeScheduleRequest;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class HrKpiWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.kpi-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $today = today();

        $pendingRequests = EmployeeScheduleRequest::query()
            ->whereNull('deleted_at')
            ->where('status', EmployeeScheduleRequest::STATUS_PENDING)
            ->count();

        $forwardedRequests = EmployeeScheduleRequest::query()
            ->whereNull('deleted_at')
            ->where('status', EmployeeScheduleRequest::STATUS_FORWARDED)
            ->count();

        $waitingForHr = EmployeeScheduleRequest::query()
            ->whereNull('deleted_at')
            ->whereIn('status', [
                EmployeeScheduleRequest::STATUS_PENDING,
                EmployeeScheduleRequest::STATUS_IN_REVIEW,
                EmployeeScheduleRequest::STATUS_FORWARDED,
            ])
            ->whereHas('approvalWorkflow.currentApprover.roles', fn (Builder $query): Builder => $query->where('name', 'hr'))
            ->count();

        return [
            'cards' => [
                [
                    'label' => 'Новые сотрудники',
                    'value' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
                    'description' => 'За последние 30 дней',
                    'url' => UserResource::getUrl('index'),
                    'color' => 'info',
                ],
                [
                    'label' => 'Испытательный срок',
                    'value' => User::query()
                        ->where('probation_enabled', true)
                        ->whereNull('probation_cancelled_at')
                        ->where(function (Builder $query) use ($today): void {
                            $query->whereNull('probation_ends_at')->orWhereDate('probation_ends_at', '>=', $today);
                        })
                        ->count(),
                    'description' => 'Сейчас на испытательном',
                    'url' => UserResource::getUrl('index'),
                    'color' => 'warning',
                ],
                [
                    'label' => 'Документы',
                    'value' => EmployeeDocument::query()
                        ->whereNull('archived_at')
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '<', $today)
                        ->count(),
                    'description' => 'Просрочены · истекают: '.EmployeeDocument::query()
                        ->whereNull('archived_at')
                        ->whereNotNull('expires_at')
                        ->whereBetween('expires_at', [$today, $today->copy()->addDays(30)])
                        ->count(),
                    'url' => UserResource::getUrl('index'),
                    'color' => 'danger',
                ],
                [
                    'label' => 'Заявки',
                    'value' => $pendingRequests,
                    'description' => "Передано: {$forwardedRequests} · HR: {$waitingForHr}",
                    'url' => EmployeeScheduleRequestResource::getUrl('index'),
                    'color' => 'success',
                ],
            ],
        ];
    }
}
