<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->findActiveCart($request);

        if ($cart) {
            $cart->load([
                'items.product.images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        return view('cart.index', [
            'cart' => $cart,
            'items' => $cart?->items ?? collect(),
        ]);
    }

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

        if (($product->availability_status ?? 'in_stock') !== 'in_stock') {
            return back()->withErrors([
                'cart' => 'Этот товар сейчас недоступен для заказа.',
            ]);
        }

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

    public function updateItem(Request $request, CartItem $cartItem): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = DB::transaction(function () use ($request, $cartItem, $data): int {
            $this->ensureCartItemBelongsToSession($request, $cartItem);

            $unitTotal = $this->getCartItemUnitTotal($cartItem);
            $cartItem->quantity = (int) $data['quantity'];
            $cartItem->line_total = round($unitTotal * $cartItem->quantity, 2);
            $cartItem->save();

            $cartItem->cart->recalculateTotals()->save();

            return $cartItem->quantity;
        });

        return back()->with('success', $this->formatCartProductQuantityMessage($quantity));
    }

    public function destroyItem(Request $request, CartItem $cartItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $cartItem) {
            $this->ensureCartItemBelongsToSession($request, $cartItem);

            $cart = $cartItem->cart;
            $cartItem->delete();
            $cart->recalculateTotals()->save();
        });

        return back()->with('success', 'Товар удалён из корзины');
    }

    private function formatCartProductQuantityMessage(int $quantity): string
    {
        return 'В корзине ' . $quantity . ' ' . $this->pluralizeRu($quantity, ['товар', 'товара', 'товаров']);
    }

    /**
     * @param array{0: string, 1: string, 2: string} $forms
     */
    private function pluralizeRu(int $number, array $forms): string
    {
        $absolute = abs($number);
        $lastTwo = $absolute % 100;
        $last = $absolute % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return $forms[2];
        }

        return match ($last) {
            1 => $forms[0],
            2, 3, 4 => $forms[1],
            default => $forms[2],
        };
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

    private function findActiveCart(Request $request): ?Cart
    {
        return Cart::query()
            ->where('session_id', $request->session()->getId())
            ->where('status', 'active')
            ->first();
    }

    private function ensureCartItemBelongsToSession(Request $request, CartItem $cartItem): void
    {
        $cartItem->loadMissing('cart');

        abort_unless(
            $cartItem->cart
                && $cartItem->cart->status === 'active'
                && $cartItem->cart->session_id === $request->session()->getId(),
            404
        );
    }

    private function getCartItemUnitTotal(CartItem $cartItem): float
    {
        $quantity = max(1, (int) $cartItem->quantity);

        if ((float) $cartItem->line_total > 0) {
            return round((float) $cartItem->line_total / $quantity, 2);
        }

        return round($cartItem->calculateLineTotal() / $quantity, 2);
    }
}
