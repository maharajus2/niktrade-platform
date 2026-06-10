<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Brand;
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
        'is_active',
        'is_featured',
        'is_new',
        'is_best_seller',
        'sort_order',
    ];

    protected $casts = [
        'shelf_life_value' => 'integer',
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
}
