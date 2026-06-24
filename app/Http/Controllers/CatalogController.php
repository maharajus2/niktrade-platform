<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $brandId = $request->integer('brand') ?: null;
        $categoryId = $request->integer('category') ?: null;
        $direction = $request->string('direction')->toString();
        $search = trim($request->string('search')->toString());

        $products = Product::query()
            ->where('is_active', true)
            ->with([
                'brand',
                'category',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when(in_array($direction, ['home', 'professional'], true), fn ($query) => $query->where('direction', $direction))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('article', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $cartProductItems = $this->getCartProductItems($request, $products->getCollection()->pluck('id')->all());

        return view('catalog.index', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'directions' => [
                'home' => 'Home',
                'professional' => 'Professional',
            ],
            'products' => $products,
            'cartProductItems' => $cartProductItems,
            'filters' => [
                'brand' => $brandId,
                'category' => $categoryId,
                'direction' => $direction,
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, string $product): View
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($product) {
                $query->where('slug', $product);

                if (ctype_digit($product)) {
                    $query->orWhere('id', (int) $product);
                }
            })
            ->with([
                'brand',
                'category',
                'productType',
                'productLine',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'certificates' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('expires_at')
                    ->orderBy('name'),
            ])
            ->firstOrFail();

        return view('catalog.show', [
            'product' => $product,
            'mainImage' => $product->images->first(),
            'galleryImages' => $product->images,
            'cartProductItem' => $cartProductItem = $this->getCartProductItem($request, $product),
            'cartProductQuantity' => (int) ($cartProductItem?->quantity ?? 0),
            'directions' => [
                'home' => 'Home',
                'professional' => 'Professional',
            ],
        ]);
    }

    private function getCartProductItem(Request $request, Product $product): ?CartItem
    {
        return CartItem::query()
            ->where('product_id', $product->id)
            ->whereHas('cart', fn ($query) => $query
                ->where('session_id', $request->session()->getId())
                ->where('status', 'active'))
            ->first();
    }

    private function getCartProductItems(Request $request, array $productIds)
    {
        if ($productIds === []) {
            return collect();
        }

        return CartItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('cart', fn ($query) => $query
                ->where('session_id', $request->session()->getId())
                ->where('status', 'active'))
            ->get()
            ->keyBy('product_id');
    }
}
