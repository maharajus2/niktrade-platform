<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\Checkout\CreateOrderFromCartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->getActiveCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('checkout.index', [
            'cart' => $cart,
            'items' => $cart->items,
        ]);
    }

    public function store(Request $request, CreateOrderFromCartService $service): RedirectResponse
    {
        $cart = $this->getActiveCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'house' => ['required', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        $cart->recalculateTotals()->save();

        $order = $service->create($cart, $data);

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        return view('checkout.success', [
            'order' => $order,
        ]);
    }

    private function getActiveCart(Request $request): ?Cart
    {
        return Cart::query()
            ->where('session_id', $request->session()->getId())
            ->where('status', 'active')
            ->with([
                'items.product.images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->first();
    }
}
