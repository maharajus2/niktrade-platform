<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class OrderKanban extends Page
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.orders.pages.order-kanban';

    protected static ?string $title = 'Доска заказов';

    #[Url(as: 'fulfillment')]
    public string $fulfillmentMethod = 'all';

    #[Url(as: 'payment')]
    public string $paymentStatus = 'all';

    #[Url(as: 'sla')]
    public string $sla = 'all';

    #[Url(as: 'archive')]
    public bool $showArchive = false;

    public function getHeading(): string
    {
        return 'Доска заказов';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('Список')
                ->url(OrderResource::getUrl('index')),
        ];
    }

    public function resetFilters(): void
    {
        $this->fulfillmentMethod = 'all';
        $this->paymentStatus = 'all';
        $this->sla = 'all';
        $this->showArchive = false;
    }

    public function moveToStatus(int $orderId, string $status): void
    {
        if (! array_key_exists($status, $this->statusColumns())) {
            return;
        }

        $order = Order::query()->find($orderId);

        if (! $order) {
            Notification::make()
                ->title('Заказ не найден')
                ->danger()
                ->send();

            return;
        }

        if ($order->status === $status) {
            return;
        }

        $order->update(['status' => $status]);

        Notification::make()
            ->title('Статус заказа обновлён')
            ->body($order->order_number . ': ' . Order::statusLabel($status))
            ->success()
            ->send();

        unset($this->columns);
    }

    #[Computed]
    public function columns(): array
    {
        $orders = $this->baseQuery()
            ->get()
            ->groupBy('status');

        $columns = [];

        foreach ($this->statusColumns() as $status => $label) {
            $columns[$status] = [
                'label' => $label,
                'orders' => $this->sortColumnOrders($orders->get($status, collect()), $status),
            ];
        }

        return $columns;
    }

    public function statusColumns(): array
    {
        return [
            Order::STATUS_NEW => 'Новый',
            Order::STATUS_ASSEMBLING => 'Собирается',
            Order::STATUS_READY_FOR_DISPATCH => 'Готов к отгрузке',
            Order::STATUS_COMPLETED => 'Завершён',
            Order::STATUS_CANCELLED => 'Отменён',
        ];
    }

    public function fulfillmentMethodOptions(): array
    {
        return [
            'all' => 'Все',
            Order::FULFILLMENT_DELIVERY => 'Доставка',
            Order::FULFILLMENT_PICKUP => 'Самовывоз',
        ];
    }

    public function paymentStatusOptions(): array
    {
        return [
            'all' => 'Все',
            Order::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
            Order::PAYMENT_STATUS_PAID => 'Оплачен',
        ];
    }

    public function slaOptions(): array
    {
        return [
            'all' => 'Все',
            Order::SLA_STATE_OVERDUE => 'Просроченные',
            Order::SLA_STATE_WARNING => 'Скоро просрочатся',
            Order::SLA_STATE_OK => 'В срок',
        ];
    }

    public function getQuickActions(Order $order): array
    {
        return match ($order->status) {
            Order::STATUS_NEW => [
                Order::STATUS_ASSEMBLING => 'Взять в сборку',
                Order::STATUS_CANCELLED => 'Отменить',
            ],
            Order::STATUS_ASSEMBLING => [
                Order::STATUS_READY_FOR_DISPATCH => 'Готов к отгрузке',
                Order::STATUS_CANCELLED => 'Отменить',
            ],
            Order::STATUS_READY_FOR_DISPATCH => [
                Order::STATUS_COMPLETED => 'Завершить',
                Order::STATUS_CANCELLED => 'Отменить',
            ],
            default => [],
        };
    }

    public function viewOrderUrl(Order $order): string
    {
        return OrderResource::getUrl('view', ['record' => $order]);
    }

    public function editOrderUrl(Order $order): string
    {
        return OrderResource::getUrl('edit', ['record' => $order]);
    }

    public function paymentStatusLabel(Order $order): string
    {
        return Order::paymentStatusLabel($order->payment_status);
    }

    public function fulfillmentStatusLabel(Order $order): string
    {
        return $order->getFulfillmentStatusLabel();
    }

    public function money(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ₽';
    }

    private function baseQuery(): Builder
    {
        $query = Order::query()
            ->whereIn('status', array_keys($this->statusColumns()));

        if (! $this->showArchive) {
            $query->whereNull('archived_at');
        }

        if ($this->fulfillmentMethod !== 'all') {
            $query->where('fulfillment_method', $this->fulfillmentMethod);
        }

        if ($this->paymentStatus !== 'all') {
            $query->where('payment_status', $this->paymentStatus);
        }

        if ($this->sla !== 'all') {
            Order::applySlaFilter($query, $this->sla);
        }

        return $query;
    }

    private function sortColumnOrders(Collection $orders, string $status): Collection
    {
        if (in_array($status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)) {
            return $orders
                ->sortByDesc(fn (Order $order): int => $this->statusTimestamp($order)?->getTimestamp() ?? $order->created_at?->getTimestamp() ?? 0)
                ->values();
        }

        return $orders
            ->sortBy(fn (Order $order): array => [
                $this->slaSortPriority($order),
                $this->statusTimestamp($order)?->getTimestamp() ?? $order->created_at?->getTimestamp() ?? 0,
                $order->id,
            ])
            ->values();
    }

    private function slaSortPriority(Order $order): int
    {
        return match ($order->getSlaState()) {
            Order::SLA_STATE_OVERDUE => 0,
            Order::SLA_STATE_WARNING => 1,
            Order::SLA_STATE_OK => 2,
            default => 3,
        };
    }

    private function statusTimestamp(Order $order): mixed
    {
        return match ($order->status) {
            Order::STATUS_ASSEMBLING => $order->assembling_at ?? $order->created_at,
            Order::STATUS_READY_FOR_DISPATCH => $order->ready_for_dispatch_at ?? $order->assembled_at ?? $order->handed_to_delivery_at ?? $order->created_at,
            Order::STATUS_COMPLETED => $order->delivered_at ?? $order->ready_for_dispatch_at ?? $order->created_at,
            Order::STATUS_CANCELLED => $order->cancelled_at ?? $order->created_at,
            default => $order->created_at,
        };
    }
}
