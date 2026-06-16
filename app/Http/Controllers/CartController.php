<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function add(Request $request, string $product): RedirectResponse
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($product) {
                $query->where('slug', $product);

                if (ctype_digit($product)) {
                    $query->orWhere('id', (int) $product);
                }
            })
            ->firstOrFail();

        DB::transaction(function () use ($request, $product) {
            $cart = Cart::query()->firstOrCreate(
                [
                    'session_id' => $request->session()->getId(),
                    'status' => 'active',
                ],
                [
                    'subtotal' => 0,
                    'discount_total' => 0,
                    'total' => 0,
                ]
            );

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            $quantity = $item ? $item->quantity + 1 : 1;
            $price = (float) ($product->price ?? 0);
            $discount = (float) ($product->discount_percent ?? 0);
            $discountedUnitPrice = $this->getDiscountedUnitPrice($product);

            if ($item) {
                $item->quantity = $quantity;
                $item->line_total = round($discountedUnitPrice * $quantity, 2);
                $item->save();
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price_snapshot' => $price,
                    'discount_snapshot' => $discount,
                    'line_total' => round($discountedUnitPrice * $quantity, 2),
                ]);
            }

            $cart->recalculateTotals()->save();
        });

        return back()->with('success', 'Товар добавлен в корзину');
    }

    private function getDiscountedUnitPrice(Product $product): float
    {
        if ($product->discounted_price !== null) {
            return (float) $product->discounted_price;
        }

        $price = (float) ($product->price ?? 0);
        $discount = (float) ($product->discount_percent ?? 0);

        return round($price - ($price * $discount / 100), 2);
    }
}
