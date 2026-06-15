<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_article',
        'product_slug',
        'product_image_path',
        'unit_price',
        'discount_percent',
        'discounted_unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discounted_unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateLineTotal(): float
    {
        $unitPrice = (float) $this->discounted_unit_price;
        $quantity = max(1, (int) $this->quantity);

        return round($unitPrice * $quantity, 2);
    }
}
