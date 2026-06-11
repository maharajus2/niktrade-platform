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

    protected static function booted(): void
    {
        static::saved(function (ProductImage $image) {
            $mainImage = ProductImage::query()
                ->where('product_id', $image->product_id)
                ->where('is_main', true)
                ->orderBy('id')
                ->first();

            if ($mainImage) {
                ProductImage::query()
                    ->where('product_id', $image->product_id)
                    ->where('id', '!=', $mainImage->id)
                    ->where('is_main', true)
                    ->update(['is_main' => false]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
