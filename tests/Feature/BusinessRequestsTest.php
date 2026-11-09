<?php

namespace Tests\Feature;

use App\Enums\BusinessRequestStatus;
use App\Models\Brand;
use App\Models\BusinessRequest;
use App\Models\BusinessRequestItem;
use App\Models\Category;
use App\Models\Contractor;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\BusinessCartService;
use App\Services\BusinessRequestService;
use App\View\Composers\BusinessLayoutComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(float $discount = 10): Contractor
    {
        return Contractor::create(['company_name' => 'Business test', 'inn' => '7707083893', 'kpp' => '770701001',
            'contact_name' => 'Test', 'email' => uniqid().'@example.test', 'phone' => '9000000000',
            'password' => 'Test-password-123', 'discount_percent' => $discount]);
    }

    private function product(array $data = []): Product
    {
        return Product::create(['name' => 'Test product', 'article' => 'ART-1', 'price' => 4000, 'business_price' => 3000,
            'brand_id' => Brand::firstOrCreate(['name' => 'Test'])->id,
            'category_id' => Category::firstOrCreate(['name' => 'Test'])->id,
            'product_type_id' => ProductType::firstOrCreate(['name' => 'Test'])->id,
            'is_active' => true, 'availability_status' => 'in_stock', ...$data]);
    }

    private function submit(Contractor $contractor): BusinessRequest
    {
        app(BusinessCartService::class)->add($contractor, $this->product(), 2);

        return app(BusinessRequestService::class)->submit($contractor, 'Комментарий');
    }

    public function test_guest_routes_redirect_to_business_home(): void
    {
        foreach (['/business/cart', '/business/requests', '/business/requests/unknown', '/business/requests/unknown/invoice'] as $url) {
            $this->get($url)->assertRedirect(route('business.home'));
        }
        $this->post('/business/cart/items')->assertRedirect(route('business.home'));
        $this->post('/business/requests')->assertRedirect(route('business.home'));
    }

    public function test_json_cart_contract_add_increment_set_zero_remove_and_html_redirect(): void
    {
        $contractor = $this->contractor();
        $product = $this->product();
        $this->actingAs($contractor, 'business');
        $response = $this->postJson('/business/cart/items', ['product_id' => $product->id]);
        $response->assertOk()->assertExactJson(['summary' => ['items_count' => 1, 'total' => 2700, 'missing' => 2300, 'min_amount' => 5000, 'can_submit' => false],
            'item' => ['product_id' => $product->id, 'quantity' => 1, 'line_total' => 2700]]);
        $this->postJson('/business/cart/items', ['product_id' => $product->id])->assertJsonPath('item.quantity', 2)->assertJsonPath('summary.can_submit', true);
        $this->assertSame(1, $contractor->cartItems()->count());
        $this->patchJson('/business/cart/items/'.$product->id, ['quantity' => 3])->assertJsonPath('summary.total', 8100);
        $this->patchJson('/business/cart/items/'.$product->id, ['quantity' => 0])->assertJsonPath('item', null)->assertJsonPath('summary.items_count', 0);
        $this->from('/business/catalog')->post('/business/cart/items', ['product_id' => $product->id])->assertRedirect('/business/catalog')->assertSessionHas('success');
        $this->deleteJson('/business/cart/items/'.$product->id)->assertJsonPath('item', null)->assertJsonPath('summary.total', 0);
        $this->assertSame(0, $contractor->cartItems()->count());
    }

    public function test_cart_rejects_inactive_out_of_stock_discontinued_and_bad_quantities(): void
    {
        $this->actingAs($this->contractor(), 'business');
        foreach ([['is_active' => false], ['availability_status' => 'out_of_stock'], ['availability_status' => 'discontinued']] as $data) {
            $product = $this->product($data);
            $this->postJson('/business/cart/items', ['product_id' => $product->id])->assertUnprocessable()->assertJsonValidationErrors('product_id');
            $this->patchJson('/business/cart/items/'.$product->id, ['quantity' => 1])->assertUnprocessable();
        }
        $product = $this->product();
        foreach ([-1, 0, 10000, 1.5] as $quantity) {
            $this->postJson('/business/cart/items', ['product_id' => $product->id, 'quantity' => $quantity])->assertUnprocessable();
        }
        $this->postJson('/business/cart/items', ['product_id' => $product->id, 'quantity' => 9999])->assertOk();
        $this->postJson('/business/cart/items', ['product_id' => $product->id])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson('/business/cart/items', ['product_id' => 999999])->assertUnprocessable();
    }

    public function test_cart_uses_current_prices_fallback_discount_and_integer_cent_sums(): void
    {
        $contractor = $this->contractor();
        $product = $this->product();
        $cart = app(BusinessCartService::class);
        $cart->add($contractor, $product, 3);
        $product->update(['business_price' => null, 'price' => 123.45]);
        $summary = $cart->summary($contractor);
        $this->assertEquals(370.35, $summary['subtotal']);
        $this->assertEquals(333.33, $summary['total']);
        $this->assertEquals(111.11, $summary['items'][0]['pricing']['price']);
        $this->assertEquals(4666.67, $summary['missing']);
        $contractor->update(['discount_percent' => 0]);
        $this->assertEquals(370.35, $cart->summary($contractor)['total']);
        $cart->clear($contractor);
        $this->assertEquals(0, $cart->summary($contractor)['items_count']);
    }

    public function test_summary_composer_uses_one_query_for_any_number_of_items(): void
    {
        $contractor = $this->contractor();
        $cart = app(BusinessCartService::class);
        foreach (range(1, 4) as $i) {
            $cart->add($contractor, $this->product(), $i);
        }
        $this->actingAs($contractor, 'business');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $view = view('layouts.business');
        app(BusinessLayoutComposer::class)->compose($view);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertSame(10, $view->getData()['bizCartCount']);
        $this->assertEquals(27000, $view->getData()['bizCartTotal']);
    }

    public function test_empty_and_below_minimum_submit_are_rejected_without_losing_cart(): void
    {
        $contractor = $this->contractor();
        $this->actingAs($contractor, 'business');
        $this->postJson('/business/requests')->assertUnprocessable()->assertJsonValidationErrors('cart');
        app(BusinessCartService::class)->add($contractor, $this->product());
        $this->postJson('/business/requests')->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('business_requests', 0);
        $this->assertSame(1, $contractor->cartItems()->count());
        $this->postJson('/business/requests', ['comment' => str_repeat('a', 1001)])->assertJsonValidationErrors('comment');
    }

    public function test_unavailable_or_unpriced_items_block_submit_and_can_still_be_removed(): void
    {
        $contractor = $this->contractor();
        $product = $this->product();
        $cart = app(BusinessCartService::class);
        $cart->add($contractor, $product, 2);
        $this->actingAs($contractor, 'business');
        foreach ([['is_active' => false], ['is_active' => true, 'availability_status' => 'out_of_stock'], ['availability_status' => 'in_stock', 'price' => null, 'business_price' => null]] as $data) {
            $product->update($data);
            $this->assertFalse($cart->summary($contractor)['can_submit']);
            $this->postJson('/business/requests')->assertUnprocessable();
        }
        $cart->setQuantity($contractor, $product, 0);
        $this->assertSame(0, $contractor->cartItems()->count());
    }

    public function test_submit_snapshots_prices_and_names_logs_creation_and_clears_only_own_cart(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 9));
        $contractor = $this->contractor();
        $other = $this->contractor();
        $product = $this->product();
        $cart = app(BusinessCartService::class);
        $cart->add($contractor, $product, 2);
        $cart->add($other, $product);
        $response = $this->actingAs($contractor, 'business')->post('/business/requests', ['comment' => 'Доставка']);
        $request = $contractor->requests()->sole();
        $response->assertRedirect(route('business.requests.show', $request->number));
        $this->assertSame('З-2026-000001', $request->number);
        $this->assertSame(BusinessRequestStatus::New, $request->status);
        $this->assertSame('6000.00', $request->subtotal);
        $this->assertSame('5400.00', $request->total);
        $this->assertSame('10.00', $request->discount_percent);
        $this->assertSame(2, $request->items_count);
        $item = $request->items()->sole();
        $this->assertSame('2700.00', $item->unit_price);
        $product->update(['name' => 'Changed', 'business_price' => 9000]);
        $contractor->update(['discount_percent' => 0]);
        $this->assertSame('Test product', $item->fresh()->name);
        $this->assertSame('2700.00', $item->fresh()->unit_price);
        $this->assertSame(0, $contractor->cartItems()->count());
        $this->assertSame(1, $other->cartItems()->count());
        $log = $request->statusLogs()->sole();
        $this->assertNull($log->from_status);
        $this->assertSame(BusinessRequestStatus::New, $log->to_status);
        $this->assertSame('Заявка создана', $log->note);
        $product->delete();
        $this->assertNull($item->fresh()->product_id);
    }

    public function test_numbering_is_shared_between_contractors_and_restarts_next_year(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 31));
        $this->assertSame('З-2026-000001', $this->submit($this->contractor())->number);
        $this->assertSame('З-2026-000002', $this->submit($this->contractor())->number);
        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->assertSame('З-2027-000001', $this->submit($this->contractor())->number);
    }

    public function test_failed_snapshot_rolls_back_request_log_and_cart_clear(): void
    {
        $contractor = $this->contractor();
        app(BusinessCartService::class)->add($contractor, $this->product(), 2);
        BusinessRequestItem::creating(fn () => throw new \RuntimeException('Snapshot failure'));
        try {
            app(BusinessRequestService::class)->submit($contractor);
            $this->fail('Expected snapshot failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Snapshot failure', $exception->getMessage());
        } finally {
            BusinessRequestItem::flushEventListeners();
        }
        $this->assertDatabaseCount('business_requests', 0);
        $this->assertDatabaseCount('business_request_status_logs', 0);
        $this->assertSame(1, $contractor->cartItems()->count());
    }

    public function test_requests_and_invoices_are_isolated_and_private(): void
    {
        Storage::fake('local');
        $owner = $this->contractor();
        $other = $this->contractor();
        $request = $this->submit($owner);
        $this->actingAs($owner, 'business')->get(route('business.requests.invoice', $request->number))->assertNotFound();
        Storage::disk('local')->put('business-invoices/test.pdf', "%PDF-1.4\nTest invoice");
        $request->update(['invoice_path' => 'business-invoices/test.pdf', 'invoice_number' => 'INV-1']);
        $this->get(route('business.requests.invoice', $request->number))->assertOk()->assertDownload('invoice-'.$request->id.'.pdf');
        $this->get(route('business.requests.show', $request->number))->assertOk();
        $this->get('/business/cart')->assertOk()->assertViewHas('summary');
        $this->actingAs($other, 'business');
        $this->get(route('business.requests.show', $request->number))->assertNotFound();
        $this->get(route('business.requests.invoice', $request->number))->assertNotFound();
        $this->get('/business/requests')->assertOk()->assertViewHas('requests', fn ($requests) => $requests->total() === 0);
        $this->assertFalse(Storage::disk('public')->exists('business-invoices/test.pdf'));
    }

    public function test_own_requests_are_paginated_by_fifteen(): void
    {
        $owner = $this->contractor();
        foreach (range(1, 16) as $i) {
            BusinessRequest::create(['number' => 'З-2026-'.str_pad($i, 6, '0', STR_PAD_LEFT), 'contractor_id' => $owner->id, 'discount_percent' => 0, 'subtotal' => 5000, 'total' => 5000, 'items_count' => 1]);
        }
        $this->actingAs($owner, 'business')->get('/business/requests')->assertViewHas('requests', fn ($requests) => $requests->total() === 16 && $requests->count() === 15);
        $this->get('/business/requests?page=2')->assertViewHas('requests', fn ($requests) => $requests->count() === 1);
    }
}
