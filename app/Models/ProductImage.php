<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'file_path',
        'alt',
        'sort_order',
        'is_main',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_main' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
