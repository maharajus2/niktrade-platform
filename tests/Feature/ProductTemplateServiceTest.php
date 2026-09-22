<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Services\Products\ProductTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_images_are_copied_to_independent_files_and_records(): void
    {
        Storage::fake('public');

        $brand = Brand::query()->create(['name' => 'Brand']);
        $category = Category::query()->create(['name' => 'Category']);
        $type = ProductType::query()->create(['name' => 'Type']);
        $line = ProductLine::query()->create([
            'brand_id' => $brand->getKey(),
            'name' => 'Line',
        ]);

        $baseData = [
            'name' => 'Product',
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'product_type_id' => $type->getKey(),
            'product_line_id' => $line->getKey(),
            'availability_status' => 'in_stock',
        ];

        $source = Product::query()->create($baseData);
        $product = Product::query()->create([...$baseData, 'name' => 'Copied product']);

        Storage::disk('public')->put('products/source.jpg', 'image-contents');
        $sourceImage = $source->images()->create([
            'file_path' => 'products/source.jpg',
            'alt' => 'Source image',
            'sort_order' => 5,
            'is_main' => true,
        ]);

        (new ProductTemplateService)->copyImages($source, $product);

        $copiedImage = $product->images()->sole();

        $this->assertNotSame($sourceImage->getKey(), $copiedImage->getKey());
        $this->assertNotSame($sourceImage->file_path, $copiedImage->file_path);
        $this->assertSame($sourceImage->alt, $copiedImage->alt);
        $this->assertSame($sourceImage->sort_order, $copiedImage->sort_order);
        $this->assertTrue($copiedImage->is_main);
        Storage::disk('public')->assertExists($sourceImage->file_path);
        Storage::disk('public')->assertExists($copiedImage->file_path);
        $this->assertSame(
            Storage::disk('public')->get($sourceImage->file_path),
            Storage::disk('public')->get($copiedImage->file_path),
        );
    }
}
