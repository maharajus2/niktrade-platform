<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Order extends Model
{
    public const FULFILLMENT_DELIVERY = 'delivery';
    public const FULFILLMENT_PICKUP = 'pickup';

    public const SLA_STATE_OK = 'ok';
    public const SLA_STATE_WARNING = 'warning';
    public const SLA_STATE_OVERDUE = 'overdue';
    public const SLA_STATE_COMPLETED = 'completed';
    public const SLA_STATE_NONE = 'none';
    public const SLA_STATE_ARCHIVED = 'archived';

    public const STATUS_NEW = 'new';
    public const STATUS_ASSEMBLING = 'assembling';
    public const STATUS_READY_FOR_DISPATCH = 'ready_for_dispatch';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const LEGACY_STATUS_PROCESSING = 'processing';
    public const LEGACY_STATUS_ASSEMBLED = 'assembled';
    public const LEGACY_STATUS_HANDED_TO_DELIVERY = 'handed_to_delivery';
    public const LEGACY_STATUS_DELIVERED = 'delivered';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const FULFILLMENT_STATUS_NOT_SENT = 'not_sent';
    public const FULFILLMENT_STATUS_SHIPPED = 'shipped';
    public const FULFILLMENT_STATUS_DELIVERED = 'delivered';
    public const FULFILLMENT_STATUS_NOT_READY = 'not_ready';
    public const FULFILLMENT_STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const FULFILLMENT_STATUS_PICKED_UP = 'picked_up';

    public const LEGACY_DELIVERY_STATUS_NOT_SHIPPED = 'not_shipped';
    public const LEGACY_DELIVERY_STATUS_SHIPPED = 'shipped';
    public const LEGACY_DELIVERY_STATUS_DELIVERED = 'delivered';
    public const LEGACY_DELIVERY_STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const LEGACY_DELIVERY_STATUS_PICKED_UP = 'picked_up';

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_address_id',
        'fulfillment_method',
        'warehouse_id',
        'warehouse_name_snapshot',
        'warehouse_address_snapshot',
        'warehouse_phone_snapshot',
        'warehouse_working_hours_snapshot',
        'status',
        'assembling_at',
        'assembled_at',
        'ready_for_dispatch_at',
        'handed_to_delivery_at',
        'delivered_at',
        'cancelled_at',
        'archived_at',
        'payment_status',
        'fulfillment_status',
        'delivery_status',
        'customer_first_name',
        'customer_last_name',
        'email',
        'phone',
        'postal_code',
        'region',
        'city',
        'street',
        'house',
        'building',
        'apartment',
        'entrance',
        'floor',
        'delivery_comment',
        'subtotal',
        'discount_total',
        'delivery_total',
        'total',
        'total_weight_grams',
        'comment',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'delivery_total' => 'decimal:2',
        'total' => 'decimal:2',
        'total_weight_grams' => 'integer',
        'warehouse_id' => 'integer',
        'assembling_at' => 'datetime',
        'assembled_at' => 'datetime',
        'ready_for_dispatch_at' => 'datetime',
        'handed_to_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if (! $order->isDirty('status')) {
                return;
            }

            $timestampColumn = match ($order->status) {
                self::STATUS_ASSEMBLING => 'assembling_at',
                self::STATUS_READY_FOR_DISPATCH => 'ready_for_dispatch_at',
                self::STATUS_COMPLETED => 'delivered_at',
                self::STATUS_CANCELLED => 'cancelled_at',
                default => null,
            };

            if ($timestampColumn && $order->{$timestampColumn} === null) {
                $order->{$timestampColumn} = now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFulfillmentMethodLabel(): string
    {
        return match ($this->fulfillment_method) {
            self::FULFILLMENT_PICKUP => 'Самовывоз',
            default => 'Доставка',
        };
    }

    public function recalculateTotals(): static
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->get();

        $subtotal = $items->sum(fn (OrderItem $item): float => (float) $item->unit_price * $item->quantity);
        $itemsTotal = $items->sum(fn (OrderItem $item): float => (float) $item->line_total);
        $totalWeightGrams = $items->sum(fn (OrderItem $item): int => $item->getLineWeightGrams() ?? 0);
        $deliveryTotal = (float) $this->delivery_total;

        $this->subtotal = round($subtotal, 2);
        $this->discount_total = round($subtotal - $itemsTotal, 2);
        $this->total = round($itemsTotal + $deliveryTotal, 2);
        $this->total_weight_grams = $totalWeightGrams > 0 ? $totalWeightGrams : null;

        return $this;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_ASSEMBLING => 'Собирается',
            self::STATUS_READY_FOR_DISPATCH => 'Готов к отгрузке',
            self::STATUS_COMPLETED => 'Завершён',
            self::STATUS_CANCELLED => 'Отменён',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'Новый',
            self::STATUS_ASSEMBLING, self::LEGACY_STATUS_PROCESSING => 'Собирается',
            self::STATUS_READY_FOR_DISPATCH,
            self::LEGACY_STATUS_ASSEMBLED,
            self::LEGACY_STATUS_HANDED_TO_DELIVERY => 'Готов к отгрузке',
            self::STATUS_COMPLETED,
            self::LEGACY_STATUS_DELIVERED => 'Завершён',
            self::STATUS_CANCELLED => 'Отменён',
            default => $status ?? '—',
        };
    }

    public static function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            self::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
            self::PAYMENT_STATUS_PAID => 'Оплачен',
            self::PAYMENT_STATUS_FAILED => 'Ошибка оплаты',
            self::PAYMENT_STATUS_REFUNDED => 'Возвращён',
            default => $status ?? '—',
        };
    }

    public static function fulfillmentStatusOptions(?string $fulfillmentMethod = null): array
    {
        return ($fulfillmentMethod ?? self::FULFILLMENT_DELIVERY) === self::FULFILLMENT_PICKUP
            ? [
                self::FULFILLMENT_STATUS_NOT_READY => 'Не готов',
                self::FULFILLMENT_STATUS_READY_FOR_PICKUP => 'Готов к выдаче',
                self::FULFILLMENT_STATUS_PICKED_UP => 'Выдан',
            ]
            : [
                self::FULFILLMENT_STATUS_NOT_SENT => 'Не отправлен',
                self::FULFILLMENT_STATUS_SHIPPED => 'Передан перевозчику',
                self::FULFILLMENT_STATUS_DELIVERED => 'Доставлен',
            ];
    }

    public static function fulfillmentStatusLabel(?string $status, ?string $fulfillmentMethod = null): string
    {
        $method = $fulfillmentMethod ?? self::FULFILLMENT_DELIVERY;

        if ($status === self::LEGACY_DELIVERY_STATUS_NOT_SHIPPED) {
            $status = $method === self::FULFILLMENT_PICKUP
                ? self::FULFILLMENT_STATUS_NOT_READY
                : self::FULFILLMENT_STATUS_NOT_SENT;
        }

        return match ($status) {
            self::FULFILLMENT_STATUS_NOT_SENT => 'Не отправлен',
            self::FULFILLMENT_STATUS_SHIPPED => $method === self::FULFILLMENT_PICKUP ? 'Не готов' : 'Передан перевозчику',
            self::FULFILLMENT_STATUS_DELIVERED => $method === self::FULFILLMENT_PICKUP ? 'Выдан' : 'Доставлен',
            self::FULFILLMENT_STATUS_NOT_READY => 'Не готов',
            self::FULFILLMENT_STATUS_READY_FOR_PICKUP => 'Готов к выдаче',
            self::FULFILLMENT_STATUS_PICKED_UP => 'Выдан',
            default => $status ?? '—',
        };
    }

    public static function deliveryStatusLabel(?string $status): string
    {
        return self::fulfillmentStatusLabel($status, self::FULFILLMENT_DELIVERY);
    }

    public function getCurrentStatusStartedAt()
    {
        return match ($this->status) {
            self::STATUS_NEW => $this->created_at,
            self::STATUS_ASSEMBLING => $this->assembling_at,
            default => null,
        };
    }

    public function getSlaDeadline()
    {
        return $this->getCurrentStatusStartedAt()?->copy()->addDay();
    }

    public function getSlaState(): string
    {
        if ($this->isArchived()) {
            return self::SLA_STATE_ARCHIVED;
        }

        if (in_array($this->status, [
            self::STATUS_READY_FOR_DISPATCH,
            self::STATUS_COMPLETED,
            self::LEGACY_STATUS_ASSEMBLED,
            self::LEGACY_STATUS_HANDED_TO_DELIVERY,
            self::LEGACY_STATUS_DELIVERED,
        ], true)) {
            return self::SLA_STATE_COMPLETED;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return self::SLA_STATE_NONE;
        }

        $deadline = $this->getSlaDeadline();

        if ($deadline === null) {
            return self::SLA_STATE_NONE;
        }

        if (now()->greaterThan($deadline)) {
            return self::SLA_STATE_OVERDUE;
        }

        if (now()->greaterThanOrEqualTo($deadline->copy()->subHours(3))) {
            return self::SLA_STATE_WARNING;
        }

        return self::SLA_STATE_OK;
    }

    public function getSlaLabel(): string
    {
        if ($this->isArchived()) {
            return 'Архив';
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return 'Отменён';
        }

        return match ($this->getSlaState()) {
            self::SLA_STATE_OK => 'В срок',
            self::SLA_STATE_WARNING => 'Скоро',
            self::SLA_STATE_OVERDUE => 'Просрочен',
            self::SLA_STATE_COMPLETED => 'Завершён',
            self::SLA_STATE_ARCHIVED => 'Архив',
            default => 'Не требуется',
        };
    }

    public function getSlaBadgeHtml(): HtmlString
    {
        $detail = $this->getSlaBadgeDetail();
        $detailHtml = $detail
            ? '<div class="text-xs font-normal opacity-80">' . e($detail) . '</div>'
            : '';

        return new HtmlString(
            '<div class="text-left leading-tight"><div class="font-semibold">'
            . e($this->getSlaLabel())
            . '</div>'
            . $detailHtml
            . '</div>'
        );
    }

    public function getSlaBadgeDetail(): ?string
    {
        $deadline = $this->getSlaDeadline();

        if ($deadline === null) {
            return null;
        }

        $minutes = now()->diffInMinutes($deadline, false);

        return match ($this->getSlaState()) {
            self::SLA_STATE_OK,
            self::SLA_STATE_WARNING => 'Осталось ' . self::formatSlaDuration($minutes),
            self::SLA_STATE_OVERDUE => self::formatSlaDuration(abs($minutes)),
            default => null,
        };
    }

    public function getSlaTimingLabel(): string
    {
        $deadline = $this->getSlaDeadline();

        if ($deadline === null) {
            return $this->getSlaLabel();
        }

        $minutes = now()->diffInMinutes($deadline, false);

        return match ($this->getSlaState()) {
            self::SLA_STATE_OK,
            self::SLA_STATE_WARNING => 'Осталось ' . self::formatSlaDuration($minutes),
            self::SLA_STATE_OVERDUE => 'Просрочен на ' . self::formatSlaDuration(abs($minutes)),
            default => $this->getSlaLabel(),
        };
    }

    public static function formatSlaDuration(int|float $minutes): string
    {
        $minutes = max(1, (int) ceil(abs($minutes)));

        if ($minutes < 60) {
            return $minutes . ' мин';
        }

        $hours = (int) ceil($minutes / 60);

        if ($hours < 24) {
            return $hours . ' ч';
        }

        $days = (int) ceil($hours / 24);

        return $days . ' ' . self::pluralizeRussian($days, 'день', 'дня', 'дней');
    }

    public function getSlaColor(): string
    {
        return match ($this->getSlaState()) {
            self::SLA_STATE_OK => 'success',
            self::SLA_STATE_WARNING => 'warning',
            self::SLA_STATE_OVERDUE => 'danger',
            self::SLA_STATE_COMPLETED => 'gray',
            self::SLA_STATE_ARCHIVED => 'gray',
            default => 'gray',
        };
    }

    public static function applySlaFilter(Builder $query, string $state): Builder
    {
        $overdueBefore = now()->subDay();
        $warningStart = now()->subDay();
        $warningEnd = now()->subHours(21);
        $okAfter = now()->subHours(21);

        return match ($state) {
            self::SLA_STATE_OVERDUE => $query->whereNull('archived_at')->where(function (Builder $query) use ($overdueBefore): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->where('created_at', '<', $overdueBefore))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->where('assembling_at', '<', $overdueBefore));
            }),
            self::SLA_STATE_WARNING => $query->whereNull('archived_at')->where(function (Builder $query) use ($warningStart, $warningEnd): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->whereBetween('created_at', [$warningStart, $warningEnd]))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->whereBetween('assembling_at', [$warningStart, $warningEnd]));
            }),
            self::SLA_STATE_OK => $query->whereNull('archived_at')->where(function (Builder $query) use ($okAfter): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->where('created_at', '>', $okAfter))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->where('assembling_at', '>', $okAfter));
            }),
            self::SLA_STATE_COMPLETED => $query->whereIn('status', [
                self::STATUS_READY_FOR_DISPATCH,
                self::STATUS_COMPLETED,
                self::LEGACY_STATUS_ASSEMBLED,
                self::LEGACY_STATUS_HANDED_TO_DELIVERY,
                self::LEGACY_STATUS_DELIVERED,
            ])->whereNull('archived_at'),
            self::SLA_STATE_NONE => $query->where('status', self::STATUS_CANCELLED)->whereNull('archived_at'),
            self::SLA_STATE_ARCHIVED => $query->whereNotNull('archived_at'),
            default => $query,
        };
    }

    public static function applySlaDefaultSort(Builder $query): Builder
    {
        $overdueBefore = now()->subDay();
        $warningStart = now()->subDay();
        $warningEnd = now()->subHours(21);

        return $query
            ->orderByRaw(
                <<<SQL
CASE
    WHEN archived_at IS NOT NULL THEN 6
    WHEN (
        (status = ? AND created_at < ?)
        OR (status = ? AND assembling_at < ?)
    ) THEN 1
    WHEN (
        (status = ? AND created_at BETWEEN ? AND ?)
        OR (status = ? AND assembling_at BETWEEN ? AND ?)
    ) THEN 2
    WHEN status IN (?, ?, ?, ?, ?) THEN 4
    WHEN status = ? THEN 5
    ELSE 3
END ASC
SQL,
                [
                    self::STATUS_NEW,
                    $overdueBefore,
                    self::STATUS_ASSEMBLING,
                    $overdueBefore,
                    self::STATUS_NEW,
                    $warningStart,
                    $warningEnd,
                    self::STATUS_ASSEMBLING,
                    $warningStart,
                    $warningEnd,
                    self::STATUS_READY_FOR_DISPATCH,
                    self::STATUS_COMPLETED,
                    self::LEGACY_STATUS_ASSEMBLED,
                    self::LEGACY_STATUS_HANDED_TO_DELIVERY,
                    self::LEGACY_STATUS_DELIVERED,
                    self::STATUS_CANCELLED,
                ],
            )
            ->orderByRaw(
                <<<SQL
CASE
    WHEN status = ? THEN created_at
    WHEN status = ? THEN COALESCE(assembling_at, created_at)
    WHEN status = ? THEN COALESCE(ready_for_dispatch_at, assembled_at, handed_to_delivery_at, created_at)
    WHEN status = ? THEN COALESCE(delivered_at, ready_for_dispatch_at, assembled_at, handed_to_delivery_at, created_at)
    WHEN status = ? THEN COALESCE(cancelled_at, created_at)
    ELSE created_at
END ASC
SQL,
                [
                    self::STATUS_NEW,
                    self::STATUS_ASSEMBLING,
                    self::STATUS_READY_FOR_DISPATCH,
                    self::STATUS_COMPLETED,
                    self::STATUS_CANCELLED,
                ],
            )
            ->orderBy('created_at');
    }

    private static function pluralizeRussian(int $number, string $one, string $few, string $many): string
    {
        $mod100 = $number % 100;
        $mod10 = $number % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $many;
        }

        return match ($mod10) {
            1 => $one,
            2, 3, 4 => $few,
            default => $many,
        };
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'gray',
            self::STATUS_ASSEMBLING, self::LEGACY_STATUS_PROCESSING => 'warning',
            self::STATUS_READY_FOR_DISPATCH,
            self::LEGACY_STATUS_ASSEMBLED,
            self::LEGACY_STATUS_HANDED_TO_DELIVERY => 'info',
            self::STATUS_COMPLETED,
            self::LEGACY_STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function canBeArchived(): bool
    {
        if ($this->isArchived()) {
            return false;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return true;
        }

        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        return $this->fulfillment_method === self::FULFILLMENT_PICKUP
            ? $this->fulfillment_status === self::FULFILLMENT_STATUS_PICKED_UP
            : $this->fulfillment_status === self::FULFILLMENT_STATUS_DELIVERED;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [
            self::STATUS_NEW,
            self::STATUS_ASSEMBLING,
            self::LEGACY_STATUS_PROCESSING,
        ], true);
    }

    public function cancelByCustomer(): void
    {
        if (! $this->canBeCancelledByCustomer()) {
            return;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }
}
