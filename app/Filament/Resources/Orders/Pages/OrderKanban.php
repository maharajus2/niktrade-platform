<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class OrderKanban extends Page
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.pages.order-kanban';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Заказы';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'view')]
    public string $viewMode = 'board';

    #[Url(as: 'fulfillment')]
    public string $fulfillmentMethod = 'all';

    #[Url(as: 'payment')]
    public string $paymentStatus = 'all';

    #[Url(as: 'sla')]
    public string $sla = 'all';

    #[Url(as: 'archive')]
    public bool $showArchive = false;

    public ?int $archiveOrderId = null;

    public function getTitle(): string|Htmlable
    {
        return 'Заказы';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('CRUD список')
                ->url(OrderResource::getUrl('index')),
        ];
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->fulfillmentMethod = 'all';
        $this->paymentStatus = 'all';
        $this->sla = 'all';
        $this->showArchive = false;
    }

    public function showBoard(): void
    {
        $this->viewMode = 'board';
    }

    public function showList(): void
    {
        $this->viewMode = 'list';
    }

    public function moveToStatus(int $orderId, string $status): void
    {
        $this->updateOrderStatus($orderId, $status, true);
    }

    public function moveOrder(int $orderId, string $targetStatus): void
    {
        $this->updateOrderStatus($orderId, $targetStatus);
    }

    private function updateOrderStatus(int $orderId, string $status, bool $withBody = false): void
    {
        if (! array_key_exists($status, $this->statusColumns())) {
            Notification::make()->title('Не удалось обновить статус заказа.')->danger()->send();

            return;
        }

        $order = Order::query()->find($orderId);

        if (! $order) {
            Notification::make()->title('Заказ не найден.')->danger()->send();

            return;
        }

        if ($order->isArchived()) {
            Notification::make()->title('Архивный заказ нельзя переместить.')->danger()->send();

            return;
        }

        if (! $this->canMoveOrder($order)) {
            Notification::make()->title('Недостаточно прав для изменения заказа.')->danger()->send();

            return;
        }

        if ($order->status === $status) {
            return;
        }

        $order->update(['status' => $status]);

        $notification = Notification::make()
            ->title('Статус заказа обновлён.')
            ->success();

        if ($withBody) {
            $notification->body($order->order_number . ': ' . Order::statusLabel($status));
        }

        $notification->send();

        unset($this->columns, $this->listOrders, $this->kpis, $this->analytics);
    }

    public function confirmArchive(int $orderId): void
    {
        $order = Order::query()->find($orderId);

        if (! $order || ! $this->canShowArchiveAction($order)) {
            return;
        }

        $this->archiveOrderId = $order->id;
    }

    public function cancelArchive(): void
    {
        $this->archiveOrderId = null;
    }

    public function archiveConfirmed(): void
    {
        if ($this->archiveOrderId === null) {
            return;
        }

        $order = Order::query()->find($this->archiveOrderId);

        if (! $order || ! $this->canShowArchiveAction($order)) {
            $this->archiveOrderId = null;

            return;
        }

        $order->update(['archived_at' => now()]);
        $this->archiveOrderId = null;

        Notification::make()->title('Заказ перемещён в архив.')->success()->send();

        unset($this->columns, $this->listOrders, $this->kpis, $this->analytics);
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

    #[Computed]
    public function listOrders(): Collection
    {
        return $this->baseQuery()
            ->latest()
            ->limit(80)
            ->get();
    }

    #[Computed]
    public function kpis(): array
    {
        $active = Order::query()->whereNull('archived_at');
        $completedToday = (clone $active)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('delivered_at', today())
            ->count();
        $overdue = Order::applySlaFilter((clone $active), Order::SLA_STATE_OVERDUE)->count();

        return [
            ['label' => 'Новые', 'value' => (clone $active)->where('status', Order::STATUS_NEW)->count(), 'delta' => '+' . (clone $active)->where('status', Order::STATUS_NEW)->whereDate('created_at', today())->count() . ' за сегодня', 'icon' => 'file', 'tone' => 'blue'],
            ['label' => 'Собираются', 'value' => (clone $active)->where('status', Order::STATUS_ASSEMBLING)->count(), 'delta' => '+' . (clone $active)->where('status', Order::STATUS_ASSEMBLING)->whereDate('assembling_at', today())->count() . ' за сегодня', 'icon' => 'package', 'tone' => 'amber'],
            ['label' => 'Готовы к отгрузке', 'value' => (clone $active)->where('status', Order::STATUS_READY_FOR_DISPATCH)->count(), 'delta' => '+' . (clone $active)->where('status', Order::STATUS_READY_FOR_DISPATCH)->whereDate('ready_for_dispatch_at', today())->count() . ' за сегодня', 'icon' => 'truck', 'tone' => 'violet'],
            ['label' => 'Завершены сегодня', 'value' => $completedToday, 'delta' => '+' . $completedToday . ' за сегодня', 'icon' => 'check-circle', 'tone' => 'green'],
            ['label' => 'Просрочены по SLA', 'value' => $overdue, 'delta' => 'Требуют внимания', 'icon' => 'alert', 'tone' => 'red'],
        ];
    }

    #[Computed]
    public function analytics(): array
    {
        $query = $this->baseQuery();
        $total = (clone $query)->count();
        $paid = (clone $query)->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $delivery = (clone $query)->where('fulfillment_method', Order::FULFILLMENT_DELIVERY)->count();
        $pickup = (clone $query)->where('fulfillment_method', Order::FULFILLMENT_PICKUP)->count();
        $overdue = Order::applySlaFilter((clone $query), Order::SLA_STATE_OVERDUE)->count();
        $ok = max(0, $total - $overdue);

        return [
            'total' => $total,
            'overdue' => $overdue,
            'slaPercent' => $total > 0 ? (int) round(($ok / $total) * 100) : 0,
            'paidPercent' => $total > 0 ? (int) round(($paid / $total) * 100) : 0,
            'deliveryPercent' => $total > 0 ? (int) round(($delivery / $total) * 100) : 0,
            'pickupPercent' => $total > 0 ? (int) round(($pickup / $total) * 100) : 0,
        ];
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
            Order::SLA_STATE_OK => 'В срок',
            Order::SLA_STATE_WARNING => 'Скоро просрочка',
            Order::SLA_STATE_OVERDUE => 'Просрочен',
        ];
    }

    public function archiveOptions(): array
    {
        return [
            false => 'Только активные',
            true => 'Показать архив',
        ];
    }

    public function getQuickActions(Order $order): array
    {
        if (! $this->canMoveOrder($order)) {
            return [];
        }

        return match ($order->status) {
            Order::STATUS_NEW => [
                Order::STATUS_ASSEMBLING => 'В сборку',
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

    public function canMoveOrder(Order $order): bool
    {
        $user = auth()->user();

        return ! $order->isArchived()
            && ($user?->hasRole('super_admin') || $user?->can('orders.status.update'));
    }

    public function canShowArchiveAction(Order $order): bool
    {
        $user = auth()->user();

        return in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)
            && $order->canBeArchived()
            && ($user?->hasRole('super_admin') || $user?->can('orders.archive'));
    }

    public function viewOrderUrl(Order $order): string
    {
        return OrderResource::getUrl('view', ['record' => $order]);
    }

    public function editOrderUrl(Order $order): string
    {
        return OrderResource::getUrl('edit', ['record' => $order]);
    }

    public function listUrl(): string
    {
        return OrderResource::getUrl('index');
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
        return number_format((float) $value, 0, ',', ' ') . ' ₽';
    }

    public function customerName(Order $order): string
    {
        return trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Покупатель';
    }

    public function locationLabel(Order $order): string
    {
        return $order->fulfillment_method === Order::FULFILLMENT_PICKUP
            ? ($order->warehouse_name_snapshot ?: 'Пункт самовывоза не указан')
            : ($order->city ?: 'Город не указан');
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

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_first_name', 'like', "%{$search}%")
                    ->orWhere('customer_last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
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
