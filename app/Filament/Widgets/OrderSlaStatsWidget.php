<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderSlaStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Контроль обработки заказов';

    protected function getStats(): array
    {
        $overdue = Order::applySlaFilter(Order::query(), Order::SLA_STATE_OVERDUE)->count();
        $warning = Order::applySlaFilter(Order::query(), Order::SLA_STATE_WARNING)->count();
        $ok = Order::applySlaFilter(Order::query(), Order::SLA_STATE_OK)->count();

        return [
            Stat::make('🔴 Просрочено', $overdue)
                ->color('danger')
                ->url($this->ordersUrl(Order::SLA_STATE_OVERDUE)),

            Stat::make('🟡 Скоро просрочатся', $warning)
                ->color('warning')
                ->url($this->ordersUrl(Order::SLA_STATE_WARNING)),

            Stat::make('🟢 В срок', $ok)
                ->color('success')
                ->url($this->ordersUrl(Order::SLA_STATE_OK)),
        ];
    }

    private function ordersUrl(string $state): string
    {
        return OrderResource::getUrl('index', [
            'tableFilters' => [
                'sla' => [
                    'value' => $state,
                ],
            ],
        ]);
    }
}
