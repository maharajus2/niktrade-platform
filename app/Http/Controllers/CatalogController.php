<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\SiteHomepageBanner;
use App\Services\Search\ProductSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    /**
     * Данные для главной страницы магазина (/catalog3).
     *
     * @return array<string, mixed>
     */
    private function homeData(Request $request): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereHas('images')
            ->with([
                'brand',
                'category',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_new')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(18)
            ->get();

        $products = $this->withExistingCatalogImages($products);

        if ($products->count() < 8) {
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
                ->orderByDesc('is_featured')
                ->orderByDesc('is_best_seller')
                ->orderByDesc('is_new')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(18)
                ->get();

            $products = $this->withExistingCatalogImages($products);
        }

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categoryProducts = $products
            ->filter(fn (Product $product): bool => $product->category_id !== null && $product->images->isNotEmpty())
            ->groupBy('category_id')
            ->map(fn (Collection $items): ?Product => $items->first());

        $brandProducts = $products
            ->filter(fn (Product $product): bool => $product->brand_id !== null && $product->images->isNotEmpty())
            ->groupBy(fn (Product $product): string => mb_strtolower((string) $product->brand?->name))
            ->map(fn (Collection $items): Collection => $items->take(4)->values());

        $homepageBanners = SiteHomepageBanner::query()
            ->active()
            ->with([
                'brand',
                'category',
                'product.images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->ordered()
            ->limit(8)
            ->get();

        return [
            'products' => $products,
            'heroProduct' => $products->first(fn (Product $product): bool => $product->images->isNotEmpty()),
            'brands' => $brands,
            'categories' => $categories,
            'categoryProducts' => $categoryProducts,
            'brandProducts' => $brandProducts,
            'homepageBanners' => $homepageBanners,
            'cartProductItems' => $this->getCartProductItems($request, $products->pluck('id')->all()),
        ];
    }

    public function catalog3(Request $request): View
    {
        return view('catalog3.index', $this->homeData($request));
    }

    private function withExistingCatalogImages(Collection $products): Collection
    {
        return $products
            ->map(function (Product $product): Product {
                $product->setRelation(
                    'images',
                    $product->images
                        ->filter(fn ($image): bool => filled($image->file_path) && Storage::disk('public')->exists($image->file_path))
                        ->values()
                );

                return $product;
            })
            ->filter(fn (Product $product): bool => $product->images->isNotEmpty())
            ->values();
    }

    public function index(Request $request): View
    {
        $brandId = $request->integer('brand') ?: null;
        $categoryId = $request->integer('category') ?: null;
        $direction = $request->string('direction')->toString();
        $search = trim($request->string('search')->toString());

        // Если точных совпадений нет, ищем с допуском опечаток и неверной раскладки.
        $fuzzyIds = null;

        if ($search !== '') {
            $hasDirectMatch = Product::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%"))
                ->exists();

            if (! $hasDirectMatch) {
                $fuzzyIds = app(ProductSearchService::class)->ids($search);
            }
        }

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
            ->when($fuzzyIds !== null, fn ($query) => $query->whereIn('id', $fuzzyIds ?: [0]))
            ->when($search !== '' && $fuzzyIds === null, function ($query) use ($search) {
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

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->with([
                'brand',
                'category',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_main')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('catalog.show', [
            'product' => $product,
            'mainImage' => $product->images->first(),
            'galleryImages' => $product->images,
            'cartProductItem' => $cartProductItem = $this->getCartProductItem($request, $product),
            'cartProductQuantity' => (int) ($cartProductItem?->quantity ?? 0),
            'relatedProducts' => $relatedProducts,
            'cartProductItems' => $this->getCartProductItems($request, $relatedProducts->modelKeys()),
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
