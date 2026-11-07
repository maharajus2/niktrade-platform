<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::create([
            'name' => 'Test product',
            'brand_id' => Brand::firstOrCreate(['name' => 'Test brand'])->id,
            'category_id' => Category::firstOrCreate(['name' => 'Test category'])->id,
            'product_type_id' => ProductType::firstOrCreate(['name' => 'Test type'])->id,
            'is_active' => true,
            'price' => 100,
            ...$attributes,
        ]);
    }

    public function test_related_products_are_limited_to_four_active_products_in_the_same_category(): void
    {
        $product = $this->product(['slug' => 'main']);
        $expected = [];
        for ($index = 1; $index <= 6; $index++) {
            $related = $this->product(['name' => 'Related '.$index, 'sort_order' => $index]);
            if ($index <= 4) {
                $expected[] = $related->id;
            }
        }
        $this->product(['name' => 'Inactive', 'is_active' => false]);
        $this->product(['name' => 'Other category', 'category_id' => Category::create(['name' => 'Other category'])->id]);

        $this->get('/catalog/main')->assertOk()->assertViewHas('relatedProducts', function ($related) use ($expected): bool {
            $this->assertSame($expected, $related->modelKeys());
            foreach ($related as $record) {
                $this->assertTrue($record->relationLoaded('brand'));
                $this->assertTrue($record->relationLoaded('images'));
            }

            return true;
        });
    }

    public function test_empty_description_and_documents_are_hidden_and_seo_is_preserved(): void
    {
        $product = $this->product(['seo_title' => 'Product SEO title', 'seo_description' => 'Product SEO description', 'description' => '  ']);
        $this->get('/catalog/'.$product->id)->assertOk()
            ->assertSee('<title>Product SEO title</title>', false)
            ->assertSee('content="Product SEO description"', false)
            ->assertSee('class="v3-top"', false)
            ->assertSee('shop-product.css', false)
            ->assertDontSee('<h2>Описание</h2>', false)
            ->assertDontSee('Описание пока не заполнено')
            ->assertDontSee('id="product-tab-documents"', false)
            ->assertDontSee('id="product-panel-documents"', false);

        $withoutSeo = $this->product();
        $this->get('/catalog/'.$withoutSeo->id)->assertOk()->assertDontSee('name="description"', false);
    }

    public function test_document_tab_is_shown_for_an_active_certificate_or_instruction(): void
    {
        $withCertificate = $this->product();
        $certificate = Certificate::create(['name' => 'Test certificate', 'is_active' => true, 'is_permanent' => true]);
        $withCertificate->certificates()->attach($certificate);
        $this->get('/catalog/'.$withCertificate->id)->assertOk()
            ->assertSee('id="product-tab-documents"', false)
            ->assertSee('Test certificate');

        $withInstruction = $this->product(['instruction_file_path' => 'test-instruction.pdf']);
        $this->get('/catalog/'.$withInstruction->id)->assertOk()
            ->assertSee('id="product-tab-documents"', false)
            ->assertSee('test-instruction.pdf');
    }

    public function test_inactive_products_remain_unavailable(): void
    {
        $product = $this->product(['is_active' => false]);
        $this->get('/catalog/'.$product->id)->assertNotFound();
    }
}
