<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteHomepageBanner extends Model
{
    public const LINK_CATALOG = 'catalog';
    public const LINK_PRODUCTS = 'products';
    public const LINK_PROMOS = 'promos';
    public const LINK_BRAND = 'brand';
    public const LINK_CATEGORY = 'category';
    public const LINK_PRODUCT = 'product';
    public const LINK_CUSTOM = 'custom';

    public const FONT_DEFAULT = 'default';
    public const FONT_GEOMETRIC = 'geometric';
    public const FONT_ROUNDED = 'rounded';
    public const FONT_SERIF = 'serif';
    public const FONT_CONDENSED = 'condensed';

    protected $fillable = [
        'eyebrow',
        'title',
        'subtitle',
        'description',
        'badge_text',
        'button_label',
        'link_type',
        'product_id',
        'brand_id',
        'category_id',
        'external_url',
        'image_path',
        'mobile_image_path',
        'theme',
        'text_color',
        'font_family',
        'opens_in_new_tab',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'opens_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function linkTypeOptions(): array
    {
        return [
            self::LINK_CATALOG => 'Каталог',
            self::LINK_PRODUCTS => 'Популярные товары',
            self::LINK_PROMOS => 'Акции',
            self::LINK_BRAND => 'Бренд',
            self::LINK_CATEGORY => 'Категория',
            self::LINK_PRODUCT => 'Товар',
            self::LINK_CUSTOM => 'Своя ссылка',
        ];
    }

    public static function fontFamilyOptions(): array
    {
        return [
            self::FONT_DEFAULT => 'По умолчанию',
            self::FONT_GEOMETRIC => 'Геометрический',
            self::FONT_ROUNDED => 'Округлый',
            self::FONT_SERIF => 'Антиква',
            self::FONT_CONDENSED => 'Компактный',
        ];
    }

    public static function fontFamilyStacks(): array
    {
        return [
            self::FONT_DEFAULT => null,
            self::FONT_GEOMETRIC => 'Inter, Arial, sans-serif',
            self::FONT_ROUNDED => '"Trebuchet MS", "Arial Rounded MT Bold", Inter, Arial, sans-serif',
            self::FONT_SERIF => 'Georgia, "Times New Roman", serif',
            self::FONT_CONDENSED => '"Arial Narrow", "Roboto Condensed", Arial, sans-serif',
        ];
    }

    public static function themeOptions(): array
    {
        return [
            'blue' => 'Синий',
            'green' => 'Зеленый',
            'cyan' => 'Бирюзовый',
            'dark' => 'Темный',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->orderBy('id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function resolveUrl(): string
    {
        return match ($this->link_type) {
            self::LINK_PRODUCTS => '#products',
            self::LINK_PROMOS => '#promos',
            self::LINK_BRAND => $this->brand_id ? route('catalog.index', ['brand' => $this->brand_id]) : route('catalog.index'),
            self::LINK_CATEGORY => $this->category_id ? route('catalog.index', ['category' => $this->category_id]) : route('catalog.index'),
            self::LINK_PRODUCT => $this->product ? route('catalog.show', $this->product->slug ?: $this->product->id) : route('catalog.index'),
            self::LINK_CUSTOM => $this->external_url ?: route('catalog.index'),
            default => route('catalog.index'),
        };
    }
}
