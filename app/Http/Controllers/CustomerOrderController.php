<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
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
