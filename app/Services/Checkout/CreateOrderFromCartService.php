<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCartService
{
    public function create(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data): Order {
            $cart->loadMissing(['items.product.images']);

            $customer = $this->resolveCustomer($data);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'customer_address_id' => $data['customer_address_id'] ?? null,
                'status' => Order::STATUS_NEW,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'delivery_status' => Order::DELIVERY_STATUS_NOT_SHIPPED,
                'customer_first_name' => $data['first_name'] ?? null,
                'customer_last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'postal_code' => $data['postal_code'] ?? null,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'street' => $data['street'] ?? null,
                'house' => $data['house'] ?? null,
                'building' => $data['building'] ?? null,
                'apartment' => $data['apartment'] ?? null,
                'entrance' => $data['entrance'] ?? null,
                'floor' => $data['floor'] ?? null,
                'delivery_comment' => $data['delivery_comment'] ?? null,
                'subtotal' => $cart->subtotal,
                'discount_total' => $cart->discount_total,
                'delivery_total' => $data['delivery_total'] ?? 0,
                'total' => (float) $cart->total + (float) ($data['delivery_total'] ?? 0),
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $this->createOrderItem($order, $item);
            }

            $order->recalculateTotals()->save();

            $cart->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $order->load('items');
        });
    }

    protected function resolveCustomer(array $data): Customer
    {
        $customer = Customer::query()
            ->where('email', $data['email'])
            ->orWhere('phone', $data['phone'])
            ->first();

        if ($customer) {
            return $customer;
        }

        return Customer::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_active' => true,
            'is_quick_registered' => true,
        ]);
    }

    protected function createOrderItem(Order $order, CartItem $item): OrderItem
    {
        $product = $item->product;
        $discountPercent = (float) $item->discount_snapshot;
        $unitPrice = (float) $item->price_snapshot;
        $discountedUnitPrice = round($unitPrice - ($unitPrice * $discountPercent / 100), 2);

        return $order->items()->create([
            'product_id' => $item->product_id,
            'product_name' => $product?->name ?? 'Товар удален',
            'product_article' => $product?->article,
            'product_slug' => $product?->slug,
            'product_image_path' => $this->getProductImagePath($item),
            'weight_snapshot_value' => $product?->weight_value,
            'weight_snapshot_unit' => $product?->weight_unit,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discounted_unit_price' => $discountedUnitPrice,
            'quantity' => $item->quantity,
            'line_total' => $item->line_total,
        ]);
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    protected function getProductImagePath(CartItem $item): ?string
    {
        $product = $item->product;

        if (! $product) {
            return null;
        }

        /** @var ProductImage|null $image */
        $image = $product->images
            ->firstWhere('is_main', true)
            ?? $product->images->first();

        return $image?->file_path;
    }
}
