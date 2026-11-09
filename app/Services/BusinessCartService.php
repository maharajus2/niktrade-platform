<?php

namespace App\Services;

use App\Models\Contractor;
use App\Models\Product;
use App\Support\BusinessPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessCartService
{
    public function add(Contractor $contractor, Product $product, int $qty = 1): void
    {
        $this->validateQuantity($qty);
        DB::transaction(function () use ($contractor, $product, $qty): void {
            Contractor::whereKey($contractor->id)->lockForUpdate()->firstOrFail();
            $this->availableProduct($product);
            $item = $contractor->cartItems()->firstOrNew(['product_id' => $product->id]);
            $quantity = ($item->quantity ?? 0) + $qty;
            $this->validateQuantity($quantity);
            $item->quantity = $quantity;
            $item->save();
        }, 3);
    }

    public function setQuantity(Contractor $contractor, Product $product, int $qty): void
    {
        if ($qty === 0) {
            $this->remove($contractor, $product);

            return;
        }
        $this->validateQuantity($qty);
        DB::transaction(function () use ($contractor, $product, $qty): void {
            Contractor::whereKey($contractor->id)->lockForUpdate()->firstOrFail();
            $this->availableProduct($product);
            $contractor->cartItems()->updateOrCreate(['product_id' => $product->id], ['quantity' => $qty]);
        }, 3);
    }

    public function remove(Contractor $contractor, Product $product): void
    {
        DB::transaction(function () use ($contractor, $product): void {
            Contractor::whereKey($contractor->id)->lockForUpdate()->firstOrFail();
            $contractor->cartItems()->where('product_id', $product->id)->delete();
        }, 3);
    }

    public function clear(Contractor $contractor): void
    {
        DB::transaction(function () use ($contractor): void {
            Contractor::whereKey($contractor->id)->lockForUpdate()->firstOrFail();
            $contractor->cartItems()->delete();
        }, 3);
    }

    public function summary(Contractor $contractor, bool $lock = false): array
    {
        // A single query also supplies the business layout totals without N+1 queries.
        $query = Product::query()->join('business_cart_items', 'products.id', '=', 'business_cart_items.product_id')
            ->where('business_cart_items.contractor_id', $contractor->id)
            ->select('products.*', 'business_cart_items.quantity as business_quantity')
            ->orderBy('products.id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $subtotal = $total = $count = 0;
        $valid = true;
        $items = $query->get()->map(function (Product $product) use ($contractor, &$subtotal, &$total, &$count, &$valid): array {
            $pricing = BusinessPricing::for($product, $contractor);
            $quantity = (int) $product->business_quantity;
            $available = $product->is_active && $product->availability_status === 'in_stock';
            $valid = $valid && $available && $pricing['price'] !== null && $quantity >= 1 && $quantity <= 9999;
            $lineCents = $pricing['price'] === null ? null : (int) round($pricing['price'] * 100) * $quantity;
            $subtotal += (int) round(($pricing['list'] ?? 0) * 100) * $quantity;
            $total += $lineCents ?? 0;
            $count += $quantity;

            return ['product' => $product, 'product_id' => $product->id, 'quantity' => $quantity,
                'pricing' => $pricing, 'list_price' => $pricing['list'], 'unit_price' => $pricing['price'],
                'line_total' => $lineCents === null ? null : $lineCents / 100, 'available' => $available];
        });
        $minimum = (float) config('business.min_request_amount', 5000);

        return ['items' => $items, 'items_count' => $count, 'subtotal' => $subtotal / 100, 'total' => $total / 100,
            'min_amount' => $minimum, 'missing' => max(0, round($minimum - $total / 100, 2)),
            'can_submit' => $items->isNotEmpty() && $valid && $total >= (int) round($minimum * 100)];
    }

    private function validateQuantity(int $qty): void
    {
        if ($qty < 1 || $qty > 9999) {
            throw ValidationException::withMessages(['quantity' => 'Количество должно быть от 1 до 9999.']);
        }
    }

    private function availableProduct(Product $product): void
    {
        $current = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
        if (! $current->is_active || $current->availability_status !== 'in_stock') {
            throw ValidationException::withMessages(['product_id' => 'Товар недоступен для заказа.']);
        }
    }
}
