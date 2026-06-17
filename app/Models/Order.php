<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
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
    ];

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
