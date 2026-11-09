<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'article',
        'brand_id',
        'product_line_id',
        'product_type_id',
        'category_id',
        'short_description',
        'description',
        'composition',
        'usage_method',
        'storage_conditions',
        'precautions',
        'disposal_method',
        'availability_status',
        'shelf_life_value',
        'shelf_life_unit',
        'volume_value',
        'volume_unit',
        'weight_value',
        'weight_unit',
        'price',
        'business_price',
        'discount_percent',
        'discounted_price',
        'direction',
        'instruction_file_path',
        'barcode',
        'is_active',
        'is_featured',
        'is_new',
        'is_best_seller',
        'sort_order',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'shelf_life_value' => 'integer',
        'volume_value' => 'integer',
        'weight_value' => 'decimal:3',
        'discount_percent' => 'float',
        'price' => 'decimal:2',
        'business_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_best_seller' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if ($product->business_price === null) {
                $product->business_price = $product->price;
            }
        });

        static::saving(function (Product $product) {
            if ($product->price !== null) {
                if ($product->discount_percent !== null && $product->discounted_price === null) {
                    $product->discounted_price = round(
                        $product->price - ($product->price * $product->discount_percent / 100),
                        2
                    );
                }

                if ($product->discounted_price !== null && $product->discount_percent === null && $product->price > 0) {
                    $product->discount_percent = round(
                        (($product->price - $product->discounted_price) / $product->price) * 100,
                        1
                    );
                }
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function businessListPrice(): ?float
    {
        $price = $this->business_price ?? $this->price;

        return $price === null ? null : (float) $price;
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class)
            ->withTimestamps();
    }
}
