<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'customer_id',
        'session_id',
        'status',
        'subtotal',
        'discount_total',
        'total',
        'converted_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculateTotals(): static
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->get();

        $subtotal = $items->sum(fn (CartItem $item): float => (float) $item->price_snapshot * $item->quantity);
        $total = $items->sum(fn (CartItem $item): float => (float) $item->line_total);

        $this->subtotal = round($subtotal, 2);
        $this->discount_total = round($subtotal - $total, 2);
        $this->total = round($total, 2);

        return $this;
    }
}
