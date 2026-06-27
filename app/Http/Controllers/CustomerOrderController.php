<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\WeightFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');
        $activeTab = in_array($request->query('tab'), ['active', 'archive', 'all'], true)
            ? $request->query('tab')
            : 'active';

        $ordersQuery = $customer->orders()->latest();

        if ($activeTab === 'active') {
            $ordersQuery->whereNull('archived_at');
        }

        if ($activeTab === 'archive') {
            $ordersQuery->whereNotNull('archived_at');
        }

        return view('customer-account.orders.index', [
            'activeTab' => $activeTab,
            'counts' => [
                'active' => $customer->orders()->whereNull('archived_at')->count(),
                'archive' => $customer->orders()->whereNotNull('archived_at')->count(),
                'all' => $customer->orders()->count(),
            ],
            'orders' => $ordersQuery->paginate(12)->withQueryString(),
        ]);
    }

    public function show(Request $request, Order $order): View|Response
    {
        if ($order->customer_id !== $request->user('customer')?->id) {
            return response()->view('customer-account.orders.forbidden', [
                'order' => $order,
            ], 403);
        }

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

    public function switchAccount(Request $request, Order $order): RedirectResponse
    {
        $request->session()->put('url.intended', route('customer.account.orders.show', $order));

        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
