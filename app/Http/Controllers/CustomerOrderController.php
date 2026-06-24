<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\WeightFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer-account.orders.index', [
            'orders' => $request->user('customer')
                ->orders()
                ->latest()
                ->paginate(12),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->customer_id === $request->user('customer')?->id, 403);

        $order->load([
            'items.product.images' => fn ($query) => $query
                ->orderByDesc('is_main')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('customer-account.orders.show', [
            'order' => $order,
            'totalWeight' => WeightFormatter::formatGrams($order->total_weight_grams),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === $request->user('customer')?->id, 403);

        if (! $order->canBeCancelledByCustomer()) {
            return back()->withErrors([
                'order' => 'Этот заказ уже нельзя отменить.',
            ]);
        }

        $order->cancelByCustomer();

        return back()->with('success', 'Заказ отменён.');
    }
}
