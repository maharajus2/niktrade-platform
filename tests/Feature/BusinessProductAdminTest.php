<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return [
            'name' => 'Business admin product',
            'brand_id' => Brand::firstOrCreate(['name' => 'Brand'])->id,
            'category_id' => Category::firstOrCreate(['name' => 'Category'])->id,
            'product_type_id' => ProductType::firstOrCreate(['name' => 'Type'])->id,
            'price' => 100,
            'is_active' => true,
            'availability_status' => 'in_stock',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user, 'web');
    }

    public function test_admin_can_create_and_edit_business_price_without_changing_retail_price(): void
    {
        Livewire::test(CreateProduct::class)->fillForm([...$this->data(), 'business_price' => 125.55])
            ->call('create')->assertHasNoFormErrors();
        $product = Product::sole();
        $this->assertSame('125.55', $product->business_price);
        Livewire::test(EditProduct::class, ['record' => $product->id])->fillForm(['business_price' => 95.25])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('95.25', $product->fresh()->business_price);
        $this->assertSame('100.00', $product->fresh()->price);
    }

    public function test_clearing_business_price_saves_null_and_uses_retail_fallback(): void
    {
        $product = Product::create([...$this->data(), 'business_price' => 80]);
        Livewire::test(EditProduct::class, ['record' => $product->id])->fillForm(['business_price' => ''])
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull($product->fresh()->business_price);
        $this->assertSame(100.0, $product->fresh()->businessListPrice());
    }

    public function test_admin_rejects_negative_business_price(): void
    {
        $product = Product::create($this->data());
        Livewire::test(EditProduct::class, ['record' => $product->id])->fillForm(['business_price' => -1])
            ->call('save')->assertHasFormErrors(['business_price']);
        $this->assertSame('100.00', $product->fresh()->business_price);
    }
}
