<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contractor;
use App\Models\Product;
use App\Services\Search\ProductSearchService;
use App\Support\BusinessPricing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BusinessCatalogController extends Controller
{
    public function __construct(private readonly ProductSearchService $search) {}

    public function index(Request $request): View
    {
        $contractor = $request->user('business');
        $brandId = $request->integer('brand') ?: null;
        $categoryId = $request->integer('category') ?: null;
        $direction = $request->string('direction')->toString();
        $search = trim($request->string('search')->toString());
        $sortOptions = [
            'default' => 'По умолчанию',
            'name_asc' => 'Название: А–Я',
            'name_desc' => 'Название: Я–А',
            'price_asc' => 'Сначала дешевле',
            'price_desc' => 'Сначала дороже',
        ];
        $sort = $request->string('sort', 'default')->toString();
        if (! array_key_exists($sort, $sortOptions)) {
            $sort = 'default';
        }

        $fuzzyIds = null;
        if ($search !== '') {
            $hasDirectMatch = Product::where('is_active', true)
                ->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%"))->exists();
            if (! $hasDirectMatch) {
                $fuzzyIds = $this->search->ids($search);
            }
        }

        $query = Product::where('is_active', true)->with($this->catalogRelations())
            ->when($brandId, fn (Builder $query) => $query->where('brand_id', $brandId))
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when(in_array($direction, ['home', 'professional'], true), fn (Builder $query) => $query->where('direction', $direction))
            ->when($fuzzyIds !== null, fn (Builder $query) => $query->whereIn('id', $fuzzyIds ?: [0]))
            ->when($search !== '' && $fuzzyIds === null, fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%")));

        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $order = $sort === 'price_asc' ? 'asc' : 'desc';
            $query->orderByRaw('CASE WHEN COALESCE(business_price, price) IS NULL THEN 1 ELSE 0 END')
                ->orderByRaw('COALESCE(business_price, price) '.$order);
        } elseif ($sort === 'default') {
            $query->orderBy('sort_order');
        }
        $query->orderBy('name', $sort === 'name_desc' ? 'desc' : 'asc')->orderBy('id');
        $products = $query->paginate(24)->withQueryString();
        $products->through(fn (Product $product): Product => $this->withPricing($product, $contractor));

        return view('business.catalog.index', [
            'products' => $products,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'directions' => ['home' => 'Home', 'professional' => 'Professional'],
            'filters' => ['brand' => $brandId, 'category' => $categoryId, 'direction' => $direction, 'search' => $search, 'sort' => $sort],
            'sortOptions' => $sortOptions,
            'contractor' => $contractor,
            // Keep the view data contract without exposing the retail cart in the business portal.
            'cartProductItems' => collect(),
        ]);
    }

    public function show(Request $request, string $product): View
    {
        $contractor = $request->user('business');
        $product = Product::where('is_active', true)
            ->where(function (Builder $query) use ($product): void {
                $query->where('slug', $product);
                if (ctype_digit($product)) {
                    $query->orWhere('id', (int) $product);
                }
            })
            ->with([
                ...$this->catalogRelations(),
                'productType',
                'productLine',
                'certificates' => fn ($query) => $query->where('is_active', true)->orderBy('expires_at')->orderBy('name'),
            ])->firstOrFail();

        $relatedProducts = Product::where('is_active', true)
            ->where('category_id', $product->category_id)->whereKeyNot($product->getKey())
            ->with($this->catalogRelations())->orderBy('sort_order')->orderBy('name')->limit(4)->get()
            ->map(fn (Product $related): Product => $this->withPricing($related, $contractor));
        $this->withPricing($product, $contractor);

        return view('business.catalog.show', [
            'product' => $product,
            'mainImage' => $product->images->first(),
            'galleryImages' => $product->images,
            'relatedProducts' => $relatedProducts,
            'directions' => ['home' => 'Home', 'professional' => 'Professional'],
            'contractor' => $contractor,
            'cartProductItem' => null,
            'cartProductQuantity' => 0,
            'cartProductItems' => collect(),
        ]);
    }

    private function catalogRelations(): array
    {
        return [
            'brand',
            'category',
            'images' => fn ($query) => $query->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id'),
        ];
    }

    private function withPricing(Product $product, Contractor $contractor): Product
    {
        $product->business_pricing = BusinessPricing::for($product, $contractor);

        return $product;
    }
}
