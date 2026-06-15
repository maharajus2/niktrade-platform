<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price_snapshot',
        'discount_snapshot',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_snapshot' => 'decimal:2',
        'discount_snapshot' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateLineTotal(): float
    {
        $price = (float) $this->price_snapshot;
        $discount = (float) $this->discount_snapshot;
        $quantity = max(1, (int) $this->quantity);
        $unitTotal = $price - ($price * $discount / 100);

        return round($unitTotal * $quantity, 2);
    }
}
