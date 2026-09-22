<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Products\ProductTemplateService;
use PHPUnit\Framework\TestCase;

class ProductTemplateServiceTest extends TestCase
{
    public function test_template_copies_reusable_fields_and_resets_identifiers_and_publication(): void
    {
        $source = new Product([
            'name' => 'Исходный товар',
            'slug' => 'source-product',
            'article' => 'SOURCE-SKU',
            'barcode' => '4600000000001',
            'brand_id' => 10,
            'category_id' => 20,
            'product_type_id' => 30,
            'product_line_id' => 40,
            'description' => 'Описание',
            'composition' => 'Состав',
            'price' => 199.90,
            'instruction_file_path' => 'product-instructions/source.pdf',
            'is_active' => true,
            'seo_title' => 'SEO',
        ]);

        $data = (new ProductTemplateService)->getTemplateData($source);

        $this->assertSame('Исходный товар', $data['name']);
        $this->assertSame(10, $data['brand_id']);
        $this->assertSame(20, $data['category_id']);
        $this->assertSame(30, $data['product_type_id']);
        $this->assertSame(40, $data['product_line_id']);
        $this->assertSame('Описание', $data['description']);
        $this->assertSame('Состав', $data['composition']);
        $this->assertSame('SEO', $data['seo_title']);
        $this->assertNull($data['slug']);
        $this->assertNull($data['article']);
        $this->assertNull($data['barcode']);
        $this->assertNull($data['instruction_file_path']);
        $this->assertFalse($data['is_active']);
        $this->assertTrue($data['copy_source_images']);
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('certificates', $data);
        $this->assertArrayNotHasKey('created_at', $data);
        $this->assertArrayNotHasKey('updated_at', $data);
    }
}
