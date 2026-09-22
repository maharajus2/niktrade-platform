<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductTemplateService
{
    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(Product $source): array
    {
        return [
            'name' => $source->name,
            'article' => null,
            'barcode' => null,
            'slug' => null,
            'brand_id' => $source->brand_id,
            'product_line_id' => $source->product_line_id,
            'product_type_id' => $source->product_type_id,
            'category_id' => $source->category_id,
            'short_description' => $source->short_description,
            'description' => $source->description,
            'composition' => $source->composition,
            'usage_method' => $source->usage_method,
            'storage_conditions' => $source->storage_conditions,
            'precautions' => $source->precautions,
            'disposal_method' => $source->disposal_method,
            'availability_status' => $source->availability_status,
            'shelf_life_value' => $source->shelf_life_value,
            'shelf_life_unit' => $source->shelf_life_unit,
            'volume_value' => $source->volume_value,
            'volume_unit' => $source->volume_unit,
            'weight_value' => $source->weight_value,
            'weight_unit' => $source->weight_unit,
            'price' => $source->price,
            'discount_percent' => $source->discount_percent,
            'discounted_price' => $source->discounted_price,
            'direction' => $source->direction,
            'instruction_file_path' => null,
            'is_active' => false,
            'is_featured' => $source->is_featured,
            'is_new' => $source->is_new,
            'is_best_seller' => $source->is_best_seller,
            'sort_order' => $source->sort_order,
            'seo_title' => $source->seo_title,
            'seo_description' => $source->seo_description,
            'copy_source_images' => true,
        ];
    }

    public function copyImages(Product $source, Product $product): void
    {
        $copiedPaths = [];

        try {
            foreach ($source->images as $sourceImage) {
                if (! Storage::disk('public')->exists($sourceImage->file_path)) {
                    Log::warning('Source product image was not copied because its file is missing.', [
                        'product_id' => $source->getKey(),
                        'product_image_id' => $sourceImage->getKey(),
                    ]);

                    continue;
                }

                $extension = pathinfo($sourceImage->file_path, PATHINFO_EXTENSION);
                $targetPath = 'products/'.Str::uuid().($extension === '' ? '' : '.'.$extension);

                if (! Storage::disk('public')->copy($sourceImage->file_path, $targetPath)) {
                    throw new RuntimeException("Failed to copy product image {$sourceImage->getKey()}.");
                }

                $copiedPaths[] = $targetPath;

                $product->images()->create([
                    'file_path' => $targetPath,
                    'alt' => $sourceImage->alt,
                    'sort_order' => $sourceImage->sort_order,
                    'is_main' => $sourceImage->is_main,
                ]);
            }
        } catch (Throwable $exception) {
            foreach ($copiedPaths as $copiedPath) {
                Storage::disk('public')->delete($copiedPath);
            }

            throw $exception;
        }
    }
}
