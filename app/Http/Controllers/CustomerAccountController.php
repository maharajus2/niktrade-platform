<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerAccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $request->user('customer');

        return view('customer-account.dashboard', [
            'customer' => $customer,
            'ordersCount' => $customer->orders()->count(),
            'addressesCount' => $customer->addresses()->count(),
            'totalSpent' => $customer->orders()
                ->where('status', Order::STATUS_DELIVERED)
                ->sum('total'),
            'totalOrderedWeightGrams' => $customer->orders()
                ->sum('total_weight_grams'),
        ]);
    }
}
