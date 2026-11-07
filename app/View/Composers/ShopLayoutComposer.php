<?php

namespace App\View\Composers;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductName;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Общие данные для каркаса магазина (layouts.shop): меню, корзина, мини-корзина.
 */
class ShopLayoutComposer
{
    public function compose(View $view): void
    {
        $items = collect();

        if (request()->hasSession()) {
            $items = CartItem::query()
                ->whereHas('cart', fn ($query) => $query
                    ->where('session_id', request()->session()->getId())
                    ->where('status', 'active'))
                ->with(['product.brand:id,name', 'product.images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id')])
                ->orderBy('id')
                ->get();
        }

        $view->with([
            'shopCategories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'shopBrands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'shopHasSale' => Product::query()
                ->where('is_active', true)
                ->whereNotNull('discounted_price')
                ->whereColumn('discounted_price', '<', 'price')
                ->exists(),
            'shopHasNew' => Product::query()->where('is_active', true)->where('is_new', true)->exists(),
            'cartQuantity' => (int) $items->sum('quantity'),
            'cartTotal' => (float) $items->sum('line_total'),
            'dockItems' => $items->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => ProductName::withoutBrand($item->product?->name, $item->product?->brand?->name, $item->product?->direction),
                'image' => ($path = $item->product?->images->first()?->file_path) ? Storage::disk('public')->url($path) : null,
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
        ]);
    }
}
