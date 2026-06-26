<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\Warehouse;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCartService
{
    public function __construct(
        private readonly TelegramBotService $telegram,
    ) {}

    public function create(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data): Order {
            $cart->loadMissing(['items.product.images']);

            $customer = $this->resolveCustomer($data);
            $fulfillmentMethod = $data['fulfillment_method'] ?? Order::FULFILLMENT_DELIVERY;
            $isPickup = $fulfillmentMethod === Order::FULFILLMENT_PICKUP;
            $warehouse = $isPickup
                ? Warehouse::query()->where('is_active', true)->findOrFail($data['warehouse_id'])
                : null;

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'customer_address_id' => $isPickup ? null : ($data['customer_address_id'] ?? null),
                'fulfillment_method' => $fulfillmentMethod,
                'warehouse_id' => $warehouse?->id,
                'warehouse_name_snapshot' => $warehouse?->name,
                'warehouse_address_snapshot' => $warehouse?->full_address,
                'warehouse_phone_snapshot' => $warehouse?->phone,
                'warehouse_working_hours_snapshot' => $warehouse?->working_schedule_label,
                'status' => Order::STATUS_NEW,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'fulfillment_status' => $isPickup
                    ? Order::FULFILLMENT_STATUS_NOT_READY
                    : Order::FULFILLMENT_STATUS_NOT_SENT,
                'customer_first_name' => $data['first_name'] ?? null,
                'customer_last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'postal_code' => $isPickup ? null : ($data['postal_code'] ?? null),
                'region' => $isPickup ? null : ($data['region'] ?? null),
                'city' => $isPickup ? null : ($data['city'] ?? null),
                'street' => $isPickup ? null : ($data['street'] ?? null),
                'house' => $isPickup ? null : ($data['house'] ?? null),
                'building' => $isPickup ? null : ($data['building'] ?? null),
                'apartment' => $isPickup ? null : ($data['apartment'] ?? null),
                'entrance' => $isPickup ? null : ($data['entrance'] ?? null),
                'floor' => $isPickup ? null : ($data['floor'] ?? null),
                'delivery_comment' => $isPickup ? null : ($data['delivery_comment'] ?? null),
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

            $order->load('items', 'customer');

            $this->sendOrderCreatedTelegramNotification($order);

            return $order;
        });
    }

    private function sendOrderCreatedTelegramNotification(Order $order): void
    {
        $customer = $order->customer;

        if (! $customer?->telegram_id || ! $customer->telegram_verified_at) {
            return;
        }

        $this->telegram->sendMessage($customer->telegram_id, $this->orderCreatedMessage($order));
    }

    private function orderCreatedMessage(Order $order): string
    {
        return $order->fulfillment_method === Order::FULFILLMENT_PICKUP
            ? $this->pickupOrderCreatedMessage($order)
            : $this->deliveryOrderCreatedMessage($order);
    }

    private function deliveryOrderCreatedMessage(Order $order): string
    {
        $lines = [
            "📦 Никтрейд",
            "",
            "Ваш заказ принят.",
            "",
            "Заказ: {$order->order_number}",
            "Сумма: {$this->formatMoney($order->total)}",
            "Способ получения: {$order->getFulfillmentMethodLabel()}",
        ];

        $address = $this->deliveryAddress($order);

        if ($address !== '') {
            $lines[] = "";
            $lines[] = "Адрес:";
            $lines[] = $address;
        }

        $lines[] = "";
        $lines[] = "Мы сообщим, когда заказ начнут собирать.";

        return implode("\n", $lines);
    }

    private function pickupOrderCreatedMessage(Order $order): string
    {
        $lines = [
            "📦 Никтрейд",
            "",
            "Ваш заказ принят.",
            "",
            "Заказ: {$order->order_number}",
            "Сумма: {$this->formatMoney($order->total)}",
            "Способ получения: {$order->getFulfillmentMethodLabel()}",
        ];

        if ($order->warehouse_name_snapshot) {
            $lines[] = "";
            $lines[] = "Пункт самовывоза:";
            $lines[] = $order->warehouse_name_snapshot;
        }

        if ($order->warehouse_address_snapshot) {
            $lines[] = "";
            $lines[] = "Адрес:";
            $lines[] = $order->warehouse_address_snapshot;
        }

        if ($order->warehouse_working_hours_snapshot) {
            $lines[] = "";
            $lines[] = "Режим работы:";
            $lines[] = $order->warehouse_working_hours_snapshot;
        }

        $lines[] = "";
        $lines[] = "Мы сообщим, когда заказ будет готов к выдаче.";

        return implode("\n", $lines);
    }

    private function deliveryAddress(Order $order): string
    {
        return collect([
            $order->postal_code,
            $order->region,
            $order->city,
            $order->street,
            $order->house,
            $order->building,
            $order->apartment,
        ])->filter()->implode(', ');
    }

    private function formatMoney(float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ₽';
    }

    protected function resolveCustomer(array $data): Customer
    {
        if (isset($data['customer']) && $data['customer'] instanceof Customer) {
            return $data['customer'];
        }

        if (isset($data['customer_id'])) {
            return Customer::query()->findOrFail($data['customer_id']);
        }

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
