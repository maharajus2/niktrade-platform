<?php

namespace Tests\Feature;

use App\Enums\BusinessRequestStatus;
use App\Filament\Resources\BusinessRequests\BusinessRequestResource;
use App\Filament\Resources\BusinessRequests\Pages\EditBusinessRequest;
use App\Models\BusinessRequest;
use App\Models\Contractor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    private function businessRequest(): BusinessRequest
    {
        $contractor = Contractor::create(['company_name' => 'Test company', 'inn' => '7707083893', 'kpp' => '770701001',
            'contact_name' => 'Test', 'email' => 'admin-test@example.test', 'phone' => '9000000000', 'password' => 'Test-password-123']);
        $request = BusinessRequest::create(['number' => 'З-2026-000001', 'contractor_id' => $contractor->id,
            'discount_percent' => 10, 'subtotal' => 6000, 'total' => 5400, 'items_count' => 2]);
        $request->items()->create(['name' => 'Snapshot product', 'article' => 'SKU-1', 'quantity' => 2, 'list_price' => 3000, 'unit_price' => 2700, 'line_total' => 5400]);

        return $request;
    }

    private function login(string $role = 'super_admin'): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));
        $this->actingAs($user, 'web');

        return $user;
    }

    public function test_status_changes_are_logged_and_readonly_snapshot_cannot_be_changed(): void
    {
        $user = $this->login();
        $request = $this->businessRequest();
        $this->get(BusinessRequestResource::getUrl('index'))->assertOk();
        $this->get(BusinessRequestResource::getUrl('view', ['record' => $request]))->assertOk()->assertSee('Snapshot product');
        Livewire::test(EditBusinessRequest::class, ['record' => $request->id])
            ->fillForm(['status' => 'in_progress', 'manager_id' => $user->id, 'manager_comment' => 'Принята'])
            ->set('data.total', 1)->call('save')->assertHasNoFormErrors();
        $this->assertSame(BusinessRequestStatus::InProgress, $request->fresh()->status);
        $this->assertSame('5400.00', $request->fresh()->total);
        $log = $request->statusLogs()->reorder('id', 'desc')->first();
        $this->assertSame(BusinessRequestStatus::New, $log->from_status);
        $this->assertSame(BusinessRequestStatus::InProgress, $log->to_status);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Принята', $log->note);
        Livewire::test(EditBusinessRequest::class, ['record' => $request->id])->fillForm(['manager_comment' => 'Уточнение'])->call('save')->assertHasNoFormErrors();
        $this->assertSame(2, $request->statusLogs()->count());
    }

    public function test_invoiced_status_requires_invoice_number_and_pdf(): void
    {
        $this->login();
        $request = $this->businessRequest();
        Livewire::test(EditBusinessRequest::class, ['record' => $request->id])
            ->fillForm(['status' => 'invoiced'])->call('save')->assertHasFormErrors(['invoice_number', 'invoice_path']);
        $this->assertSame(BusinessRequestStatus::New, $request->fresh()->status);
        $this->assertSame(1, $request->statusLogs()->count());
    }

    public function test_pdf_upload_is_private_and_first_issued_at_is_preserved(): void
    {
        Storage::fake('local');
        $this->login();
        $request = $this->businessRequest();
        $pdf = UploadedFile::fake()->createWithContent('invoice.pdf', "%PDF-1.4\nTest invoice");
        Livewire::test(EditBusinessRequest::class, ['record' => $request->id])
            ->fillForm(['status' => 'invoiced', 'invoice_number' => 'INV-1', 'invoice_path' => $pdf])
            ->call('save')->assertHasNoFormErrors();
        $request->refresh();
        $this->assertSame(BusinessRequestStatus::Invoiced, $request->status);
        $this->assertNotNull($request->invoice_issued_at);
        $this->assertStringStartsWith('business-invoices/', $request->invoice_path);
        Storage::disk('local')->assertExists($request->invoice_path);
        $issuedAt = $request->invoice_issued_at->toDateTimeString();
        $this->travel(1)->day();
        Livewire::test(EditBusinessRequest::class, ['record' => $request->id])
            ->fillForm(['invoice_path' => UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\nReplacement")])->call('save')->assertHasNoFormErrors();
        $this->assertSame($issuedAt, $request->fresh()->invoice_issued_at->toDateTimeString());
    }

    public function test_invoice_upload_rejects_non_pdf_and_oversized_files(): void
    {
        Storage::fake('local');
        $this->login();
        $request = $this->businessRequest();
        foreach ([UploadedFile::fake()->create('text.txt', 1, 'text/plain'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            Livewire::test(EditBusinessRequest::class, ['record' => $request->id])->fillForm(['invoice_path' => $file])->call('save')->assertHasFormErrors(['invoice_path']);
        }
    }

    public function test_resource_permissions_and_no_create_or_delete_even_for_super_admin(): void
    {
        $request = $this->businessRequest();
        $this->login('staff');
        $this->assertFalse(BusinessRequestResource::canViewAny());
        $this->get(BusinessRequestResource::getUrl('index'))->assertForbidden();
        $this->get(BusinessRequestResource::getUrl('edit', ['record' => $request]))->assertForbidden();
        auth()->user()->givePermissionTo('business_requests.view_any', 'business_requests.view');
        $this->assertTrue(BusinessRequestResource::canViewAny());
        $this->assertTrue(BusinessRequestResource::canView($request));
        $this->assertFalse(BusinessRequestResource::canEdit($request));
        $this->get(BusinessRequestResource::getUrl('view', ['record' => $request]))->assertOk();
        auth()->user()->givePermissionTo('business_requests.update');
        $this->assertTrue(BusinessRequestResource::canEdit($request));
        $this->login();
        $this->assertFalse(BusinessRequestResource::canCreate());
        $this->assertFalse(BusinessRequestResource::canDelete($request));
        $this->assertFalse(BusinessRequestResource::canDeleteAny());
        $this->assertArrayNotHasKey('create', BusinessRequestResource::getPages());
    }
}
