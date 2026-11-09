<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contractor;
use App\Models\Product;
use App\Models\ProductType;
use App\Support\BusinessPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::create([
            'name' => 'Product',
            'brand_id' => Brand::firstOrCreate(['name' => 'HIBERG'])->id,
            'category_id' => Category::firstOrCreate(['name' => 'Test category'])->id,
            'product_type_id' => ProductType::firstOrCreate(['name' => 'Test type'])->id,
            'price' => 100,
            'is_active' => true,
            'availability_status' => 'in_stock',
            'direction' => 'home',
            ...$attributes,
        ]);
    }

    private function contractor(float $discount = 0): Contractor
    {
        return Contractor::create([
            'company_name' => 'Business catalog test', 'inn' => '7707083893', 'kpp' => '770701001',
            'contact_name' => 'Test contact', 'phone' => '9000000000', 'email' => 'catalog@example.test',
            'password' => 'Business-test-4321', 'discount_percent' => $discount,
        ]);
    }

    public function test_guests_cannot_access_business_catalog_or_product(): void
    {
        $this->get('/business/catalog')->assertRedirect(route('business.home'));
        $this->get('/business/catalog/missing')->assertRedirect(route('business.home'));
    }

    public function test_catalog_shows_only_active_products_with_personal_business_prices(): void
    {
        $active = $this->product(['business_price' => 120]);
        $this->product(['name' => 'Inactive', 'is_active' => false]);
        $contractor = $this->contractor(10);

        $response = $this->actingAs($contractor, 'business')->get('/business/catalog');
        $response->assertOk()->assertViewIs('business.catalog.index')
            ->assertViewHas('contractor', fn ($value) => $value->is($contractor))
            ->assertViewHas('products', function ($products) use ($active): bool {
                $this->assertSame([$active->id], $products->modelKeys());
                $this->assertSame(['list' => 120.0, 'price' => 108.0, 'discount_percent' => 10.0, 'has_discount' => true], $products->first()->business_pricing);
                foreach (['brand', 'category', 'images'] as $relation) {
                    $this->assertTrue($products->first()->relationLoaded($relation));
                }

                return true;
            });
        foreach (['brands', 'categories', 'directions', 'filters', 'sortOptions', 'cartProductItems'] as $key) {
            $response->assertViewHas($key);
        }
        $this->assertEmpty($response->viewData('cartProductItems'));
    }

    public function test_pricing_uses_business_price_and_ignores_retail_discounts(): void
    {
        $product = $this->product(['price' => 200, 'business_price' => 150, 'discount_percent' => 50]);
        $this->assertSame(['list' => 150.0, 'price' => 150.0, 'discount_percent' => 0.0, 'has_discount' => false], BusinessPricing::for($product, null));
        $this->assertSame(135.0, BusinessPricing::for($product, $this->contractor(10))['price']);
        $this->assertSame('100.00', $product->discounted_price);
    }

    public function test_zero_contractor_discount_preserves_list_price(): void
    {
        $product = $this->product(['business_price' => 123.45]);
        $this->assertSame(['list' => 123.45, 'price' => 123.45, 'discount_percent' => 0.0, 'has_discount' => false], BusinessPricing::for($product, $this->contractor()));
    }

    public function test_unset_business_price_falls_back_to_retail_price_and_rounds_discount(): void
    {
        $product = $this->product(['price' => 123.45]);
        $product->update(['business_price' => null]);
        $pricing = BusinessPricing::for($product->fresh(), $this->contractor(10));
        $this->assertSame(123.45, $pricing['list']);
        $this->assertSame(111.11, $pricing['price']);
    }

    public function test_null_prices_remain_null_and_explicit_zero_is_not_replaced(): void
    {
        $contractor = $this->contractor(10);
        $pricing = BusinessPricing::for($this->product(['price' => null]), $contractor);
        $this->assertSame(['list' => null, 'price' => null, 'discount_percent' => 10.0, 'has_discount' => false], $pricing);
        $zero = BusinessPricing::for($this->product(['price' => 100, 'business_price' => 0]), $contractor);
        $this->assertSame(0.0, $zero['list']);
        $this->assertSame(0.0, $zero['price']);
    }

    public function test_new_product_defaults_business_price_only_on_creation(): void
    {
        $product = $this->product(['price' => 100]);
        $this->assertSame('100.00', $product->fresh()->business_price);
        $product->update(['price' => 130]);
        $this->assertSame('100.00', $product->fresh()->business_price);
        $this->assertSame(100.0, $product->businessListPrice());
        $product->update(['business_price' => null]);
        $this->assertSame(130.0, $product->fresh()->businessListPrice());
    }

    public function test_migration_backfills_existing_rows_without_changing_retail_prices(): void
    {
        $priced = $this->product(['price' => 123.45, 'business_price' => 50]);
        $unpriced = $this->product(['price' => null]);
        $migration = require database_path('migrations/2026_11_09_000001_add_business_price_to_products_table.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('products', ['id' => $priced->id, 'price' => 123.45, 'business_price' => 123.45]);
        $this->assertDatabaseHas('products', ['id' => $unpriced->id, 'price' => null, 'business_price' => null]);
    }

    public function test_brand_category_and_direction_filters_are_applied_together(): void
    {
        $selected = $this->product(['name' => 'Selected', 'direction' => 'professional']);
        $this->product(['name' => 'Home']);
        $this->product(['name' => 'Other brand', 'brand_id' => Brand::create(['name' => 'Other'])->id, 'direction' => 'professional']);
        $this->product(['name' => 'Other category', 'category_id' => Category::create(['name' => 'Other'])->id, 'direction' => 'professional']);
        $query = ['brand' => $selected->brand_id, 'category' => $selected->category_id, 'direction' => 'professional'];
        $response = $this->actingAs($this->contractor(), 'business')->get('/business/catalog?'.http_build_query($query));
        $response->assertOk()->assertViewHas('products', fn ($items) => $items->modelKeys() === [$selected->id]);
        $this->assertSame($query + ['search' => '', 'sort' => 'default'], $response->viewData('filters'));
    }

    public function test_search_supports_article_typos_transliteration_and_wrong_keyboard_layout(): void
    {
        $expected = $this->product(['name' => 'Антижир спрей', 'article' => 'SKU-123']);
        $this->product(['name' => 'Unrelated', 'brand_id' => Brand::create(['name' => 'Other'])->id]);
        $this->actingAs($this->contractor(), 'business');
        foreach (['SKU-123', 'антижыр', 'хиберг', 'руиукп'] as $search) {
            $this->get('/business/catalog?'.http_build_query(['search' => $search]))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->modelKeys() === [$expected->id]);
        }
        $this->get('/business/catalog?search=zzzzzzzz')->assertViewHas('products', fn ($products) => $products->isEmpty());
    }

    public function test_pagination_has_24_items_and_keeps_filters(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->product(['name' => sprintf('Product %02d', $i)]);
        }
        $this->actingAs($this->contractor(), 'business');
        $first = $this->get('/business/catalog?direction=home');
        $first->assertOk();
        $this->assertCount(24, $first->viewData('products'));
        $this->assertSame(25, $first->viewData('products')->total());
        $this->assertStringContainsString('direction=home', $first->viewData('products')->nextPageUrl());
        $this->get('/business/catalog?direction=home&page=2')->assertOk()
            ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->first()->business_pricing['price'] === 100.0);
    }

    public function test_price_sort_uses_business_price_with_retail_fallback_and_keeps_nulls_last(): void
    {
        $a = $this->product(['name' => 'A', 'price' => 90, 'business_price' => 120]);
        $b = $this->product(['name' => 'B', 'price' => 200, 'business_price' => 50]);
        $c = $this->product(['name' => 'C', 'price' => 80]);
        $c->update(['business_price' => null]);
        $unpriced = $this->product(['name' => 'No price', 'price' => null]);
        $this->actingAs($this->contractor(10), 'business');
        $this->get('/business/catalog?sort=price_asc')->assertViewHas('products', fn ($items) => $items->modelKeys() === [$b->id, $c->id, $a->id, $unpriced->id]);
        $this->get('/business/catalog?sort=price_desc')->assertViewHas('products', fn ($items) => $items->modelKeys() === [$a->id, $c->id, $b->id, $unpriced->id]);
        $this->get('/business/catalog?sort=name_desc')->assertViewHas('products', fn ($items) => $items->modelKeys() === [$unpriced->id, $c->id, $b->id, $a->id]);
        $this->get('/business/catalog?sort=invalid')->assertViewHas('filters', fn ($filters) => $filters['sort'] === 'default');
    }

    public function test_product_and_related_products_have_pricing_and_eager_loaded_gallery(): void
    {
        $product = $this->product(['slug' => 'main', 'business_price' => 120]);
        $product->images()->create(['file_path' => 'example.png', 'is_main' => true, 'sort_order' => 0]);
        for ($i = 1; $i <= 5; $i++) {
            $this->product(['name' => 'Related '.$i, 'business_price' => 80]);
        }
        $this->product(['name' => 'Inactive', 'is_active' => false]);
        $this->product(['category_id' => Category::create(['name' => 'Other'])->id]);
        $contractor = $this->contractor(10);
        $response = $this->actingAs($contractor, 'business')->get('/business/catalog/main');
        $response->assertOk()->assertViewIs('business.catalog.show')->assertViewHas('contractor', fn ($value) => $value->is($contractor));
        $this->assertSame(108.0, $response->viewData('product')->business_pricing['price']);
        foreach (['images', 'certificates', 'productType', 'productLine', 'brand', 'category'] as $relation) {
            $this->assertTrue($response->viewData('product')->relationLoaded($relation));
        }
        $this->assertCount(1, $response->viewData('galleryImages'));
        $this->assertNotNull($response->viewData('mainImage'));
        $this->assertCount(4, $response->viewData('relatedProducts'));
        foreach ($response->viewData('relatedProducts') as $related) {
            $this->assertSame(72.0, $related->business_pricing['price']);
            $this->assertTrue($related->relationLoaded('images'));
        }
        $this->get('/business/catalog/'.$product->id)->assertOk()->assertViewHas('product', fn ($item) => $item->id === $product->id);
    }

    public function test_inactive_and_missing_products_return_404(): void
    {
        $this->product(['slug' => 'inactive', 'is_active' => false]);
        $this->actingAs($this->contractor(), 'business')->get('/business/catalog/inactive')->assertNotFound();
        $this->get('/business/catalog/missing')->assertNotFound();
    }
}
