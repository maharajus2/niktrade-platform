<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

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
        'comment',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'delivery_total' => 'decimal:2',
        'total' => 'decimal:2',
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
        $deliveryTotal = (float) $this->delivery_total;

        $this->subtotal = round($subtotal, 2);
        $this->discount_total = round($subtotal - $itemsTotal, 2);
        $this->total = round($itemsTotal + $deliveryTotal, 2);

        return $this;
    }
}
