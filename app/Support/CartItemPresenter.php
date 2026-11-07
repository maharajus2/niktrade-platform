<?php

namespace App\Support;

use App\Models\CartItem;
use Illuminate\Support\Facades\Storage;

/**
 * Данные позиции корзины для мини-корзины (JSON и первичная отрисовка).
 */
class CartItemPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function make(CartItem $item): array
    {
        $product = $item->product;
        $path = $product?->images->first()?->file_path;
        $volume = $product
            ? (ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
                ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit))
            : null;

        $meta = array_filter([
            $volume,
            $product?->article ? 'арт. ' . $product->article : null,
        ]);

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => (string) $product?->name,
            'display_name' => ProductName::withoutBrand((string) $product?->name, (string) ($product?->brand?->name ?? ''), $product?->direction),
            'meta' => implode(' · ', $meta),
            'image' => $path ? Storage::disk('public')->url($path) : null,
            'quantity' => (int) $item->quantity,
            'line_total' => number_format((float) $item->line_total, 0, ',', ' ') . ' ₽',
        ];
    }
}
