<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\Checkout\CreateOrderFromCartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->getActiveCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $customer = Auth::guard('customer')->user();
        $addresses = $customer
            ? $customer->addresses()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('checkout.index', [
            'cart' => $cart,
            'items' => $cart->items,
            'customer' => $customer,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, CreateOrderFromCartService $service): RedirectResponse
    {
        $cart = $this->getActiveCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $customer = Auth::guard('customer')->user();
        $addressIds = $customer
            ? $customer->addresses()->pluck('id')->all()
            : [];

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'fulfillment_method' => [
                'required',
                Rule::in([
                    Order::FULFILLMENT_DELIVERY,
                    Order::FULFILLMENT_PICKUP,
                ]),
            ],
            'warehouse_id' => [
                'nullable',
                'required_if:fulfillment_method,' . Order::FULFILLMENT_PICKUP,
                'integer',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'customer_address_id' => [
                'nullable',
                'integer',
                Rule::in($addressIds),
            ],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'required_if:fulfillment_method,' . Order::FULFILLMENT_DELIVERY, 'string', 'max:255'],
            'street' => ['nullable', 'required_if:fulfillment_method,' . Order::FULFILLMENT_DELIVERY, 'string', 'max:255'],
            'house' => ['nullable', 'required_if:fulfillment_method,' . Order::FULFILLMENT_DELIVERY, 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'delivery_comment' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        if ($customer) {
            $data['customer_id'] = $customer->id;
        }

        $fulfillmentMethod = $data['fulfillment_method'] ?? Order::FULFILLMENT_DELIVERY;

        if ($fulfillmentMethod === Order::FULFILLMENT_PICKUP) {
            $data['customer_address_id'] = null;
        }

        if ($fulfillmentMethod === Order::FULFILLMENT_DELIVERY && $customer && filled($data['customer_address_id'] ?? null)) {
            $address = $customer->addresses()
                ->whereKey($data['customer_address_id'])
                ->firstOrFail();

            $data = array_merge($data, $this->deliveryDataFromAddress($address, $data));
        }

        if ($fulfillmentMethod === Order::FULFILLMENT_DELIVERY
            && $customer
            && empty($data['customer_address_id'])
            && $request->boolean('save_address')) {
            $address = $customer->addresses()->create([
                'title' => $data['city'] . ', ' . $data['street'],
                'postal_code' => $data['postal_code'] ?? null,
                'region' => $data['region'] ?? null,
                'city' => $data['city'],
                'street' => $data['street'],
                'house' => $data['house'],
                'building' => $data['building'] ?? null,
                'apartment' => $data['apartment'] ?? null,
                'entrance' => $data['entrance'] ?? null,
                'floor' => $data['floor'] ?? null,
                'comment' => $data['delivery_comment'] ?? null,
                'is_default' => ! $customer->addresses()->exists(),
            ]);

            $data['customer_address_id'] = $address->id;
        }

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

    private function deliveryDataFromAddress(CustomerAddress $address, array $data): array
    {
        return [
            'postal_code' => $data['postal_code'] ?? $address->postal_code,
            'region' => $data['region'] ?? $address->region,
            'city' => $data['city'] ?? $address->city,
            'street' => $data['street'] ?? $address->street,
            'house' => $data['house'] ?? $address->house,
            'building' => $data['building'] ?? $address->building,
            'apartment' => $data['apartment'] ?? $address->apartment,
            'entrance' => $data['entrance'] ?? $address->entrance,
            'floor' => $data['floor'] ?? $address->floor,
            'delivery_comment' => $data['delivery_comment'] ?? $address->comment,
        ];
    }
}
