<?php

namespace App\Support;

use App\Models\Contractor;
use App\Models\Product;

class BusinessPricing
{
    /**
     * @return array{list: ?float, price: ?float, discount_percent: float, has_discount: bool}
     */
    public static function for(Product $product, ?Contractor $contractor): array
    {
        $list = $product->businessListPrice();
        $discount = (float) ($contractor?->discount_percent ?? 0);

        return [
            'list' => $list,
            'price' => $list === null ? null : round($list * (1 - $discount / 100), 2),
            'discount_percent' => $discount,
            'has_discount' => $list !== null && $discount > 0,
        ];
    }
}
