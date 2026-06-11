<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Brand;
use App\Models\Certificate;
use App\Models\ProductImage;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\Category;

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
        'shelf_life_value',
        'shelf_life_unit',
        'volume_value',
        'volume_unit',
        'price',
        'discount_percent',
        'direction',
        'instruction_file_path',
        'barcode',
        'is_active',
        'is_featured',
        'is_new',
        'is_best_seller',
        'sort_order',
    ];

    protected $casts = [
        'shelf_life_value' => 'integer',
        'volume_value' => 'integer',
        'discount_percent' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_best_seller' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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
