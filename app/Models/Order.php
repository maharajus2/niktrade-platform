<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Order extends Model
{
    public const SLA_STATE_OK = 'ok';
    public const SLA_STATE_WARNING = 'warning';
    public const SLA_STATE_OVERDUE = 'overdue';
    public const SLA_STATE_COMPLETED = 'completed';
    public const SLA_STATE_NONE = 'none';

    public const STATUS_NEW = 'new';
    public const STATUS_ASSEMBLING = 'assembling';
    public const STATUS_ASSEMBLED = 'assembled';
    public const STATUS_HANDED_TO_DELIVERY = 'handed_to_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const LEGACY_STATUS_PROCESSING = 'processing';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const DELIVERY_STATUS_NOT_SHIPPED = 'not_shipped';
    public const DELIVERY_STATUS_SHIPPED = 'shipped';
    public const DELIVERY_STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_address_id',
        'status',
        'assembling_at',
        'assembled_at',
        'handed_to_delivery_at',
        'delivered_at',
        'cancelled_at',
        'payment_status',
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
        'assembling_at' => 'datetime',
        'assembled_at' => 'datetime',
        'handed_to_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if (! $order->isDirty('status')) {
                return;
            }

            $timestampColumn = match ($order->status) {
                self::STATUS_ASSEMBLING => 'assembling_at',
                self::STATUS_ASSEMBLED => 'assembled_at',
                self::STATUS_HANDED_TO_DELIVERY => 'handed_to_delivery_at',
                self::STATUS_DELIVERED => 'delivered_at',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
            self::STATUS_ASSEMBLED => 'Собран',
            self::STATUS_HANDED_TO_DELIVERY => 'Передан в доставку',
            self::STATUS_DELIVERED => 'Доставлен',
            self::STATUS_CANCELLED => 'Отменён',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'Новый',
            self::STATUS_ASSEMBLING, self::LEGACY_STATUS_PROCESSING => 'Собирается',
            self::STATUS_ASSEMBLED => 'Собран',
            self::STATUS_HANDED_TO_DELIVERY => 'Передан в доставку',
            self::STATUS_DELIVERED => 'Доставлен',
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

    public static function deliveryStatusLabel(?string $status): string
    {
        return match ($status) {
            self::DELIVERY_STATUS_NOT_SHIPPED => 'Не отправлен',
            self::DELIVERY_STATUS_SHIPPED => 'Отправлен',
            self::DELIVERY_STATUS_DELIVERED => 'Доставлен',
            default => $status ?? '—',
        };
    }

    public function getCurrentStatusStartedAt()
    {
        return match ($this->status) {
            self::STATUS_NEW => $this->created_at,
            self::STATUS_ASSEMBLING => $this->assembling_at,
            self::STATUS_ASSEMBLED => $this->assembled_at,
            default => null,
        };
    }

    public function getSlaDeadline()
    {
        return $this->getCurrentStatusStartedAt()?->copy()->addDay();
    }

    public function getSlaState(): string
    {
        if (in_array($this->status, [self::STATUS_HANDED_TO_DELIVERY, self::STATUS_DELIVERED], true)) {
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
        if ($this->status === self::STATUS_CANCELLED) {
            return 'Отменён';
        }

        return match ($this->getSlaState()) {
            self::SLA_STATE_OK => 'В срок',
            self::SLA_STATE_WARNING => 'Скоро',
            self::SLA_STATE_OVERDUE => 'Просрочен',
            self::SLA_STATE_COMPLETED => 'Завершён',
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
            self::SLA_STATE_OVERDUE => $query->where(function (Builder $query) use ($overdueBefore): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->where('created_at', '<', $overdueBefore))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->where('assembling_at', '<', $overdueBefore))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLED)
                        ->where('assembled_at', '<', $overdueBefore));
            }),
            self::SLA_STATE_WARNING => $query->where(function (Builder $query) use ($warningStart, $warningEnd): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->whereBetween('created_at', [$warningStart, $warningEnd]))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->whereBetween('assembling_at', [$warningStart, $warningEnd]))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLED)
                        ->whereBetween('assembled_at', [$warningStart, $warningEnd]));
            }),
            self::SLA_STATE_OK => $query->where(function (Builder $query) use ($okAfter): void {
                $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', self::STATUS_NEW)
                        ->where('created_at', '>', $okAfter))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLING)
                        ->where('assembling_at', '>', $okAfter))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', self::STATUS_ASSEMBLED)
                        ->where('assembled_at', '>', $okAfter));
            }),
            self::SLA_STATE_COMPLETED => $query->whereIn('status', [
                self::STATUS_HANDED_TO_DELIVERY,
                self::STATUS_DELIVERED,
            ]),
            self::SLA_STATE_NONE => $query->where('status', self::STATUS_CANCELLED),
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
    WHEN (
        (status = ? AND created_at < ?)
        OR (status = ? AND assembling_at < ?)
        OR (status = ? AND assembled_at < ?)
    ) THEN 1
    WHEN (
        (status = ? AND created_at BETWEEN ? AND ?)
        OR (status = ? AND assembling_at BETWEEN ? AND ?)
        OR (status = ? AND assembled_at BETWEEN ? AND ?)
    ) THEN 2
    WHEN status IN (?, ?) THEN 4
    WHEN status = ? THEN 5
    ELSE 3
END ASC
SQL,
                [
                    self::STATUS_NEW,
                    $overdueBefore,
                    self::STATUS_ASSEMBLING,
                    $overdueBefore,
                    self::STATUS_ASSEMBLED,
                    $overdueBefore,
                    self::STATUS_NEW,
                    $warningStart,
                    $warningEnd,
                    self::STATUS_ASSEMBLING,
                    $warningStart,
                    $warningEnd,
                    self::STATUS_ASSEMBLED,
                    $warningStart,
                    $warningEnd,
                    self::STATUS_HANDED_TO_DELIVERY,
                    self::STATUS_DELIVERED,
                    self::STATUS_CANCELLED,
                ],
            )
            ->orderByRaw(
                <<<SQL
CASE
    WHEN status = ? THEN created_at
    WHEN status = ? THEN COALESCE(assembling_at, created_at)
    WHEN status = ? THEN COALESCE(assembled_at, created_at)
    WHEN status = ? THEN COALESCE(handed_to_delivery_at, created_at)
    WHEN status = ? THEN COALESCE(delivered_at, created_at)
    WHEN status = ? THEN COALESCE(cancelled_at, created_at)
    ELSE created_at
END ASC
SQL,
                [
                    self::STATUS_NEW,
                    self::STATUS_ASSEMBLING,
                    self::STATUS_ASSEMBLED,
                    self::STATUS_HANDED_TO_DELIVERY,
                    self::STATUS_DELIVERED,
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
            self::STATUS_ASSEMBLED => 'info',
            self::STATUS_HANDED_TO_DELIVERY => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [
            self::STATUS_NEW,
            self::STATUS_ASSEMBLING,
            self::STATUS_ASSEMBLED,
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
