<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Services\Messenger\MessengerService;
use App\Services\Uploads\UploadStorage;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToWriteFile;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UploadStorageTest extends TestCase
{
    use RefreshDatabase;

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('image.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aXioAAAAASUVORK5CYII='));
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Upload test', 'article' => 'UPLOAD-TEST',
            'brand_id' => Brand::create(['name' => 'Upload brand'])->id,
            'category_id' => Category::create(['name' => 'Upload category'])->id,
            'product_type_id' => ProductType::create(['name' => 'Upload type'])->id,
        ]);
    }

    private function failingDisk(string $name, bool $throws): void
    {
        $real = Storage::fake($name);
        $disk = Mockery::mock(FilesystemAdapter::class, [$real->getDriver(), $real->getAdapter(), ['throw' => $throws]])->makePartial();
        if ($throws) {
            $disk->shouldReceive('put')->andThrow(UnableToWriteFile::atLocation('test-upload'));
        } else {
            $disk->shouldReceive('put')->andReturn(false);
        }
        $disk->shouldReceive('setVisibility')->andReturn(false);
        Storage::set($name, $disk);
    }

    public function test_failed_filament_upload_does_not_create_an_image_record(): void
    {
        Storage::fake('local');
        $this->failingDisk('public', false);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user);
        $product = $this->product();

        Livewire::test(ProductImagesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'file_path' => $this->image(), 'sort_order' => 0, 'is_main' => true,
            ])
            ->assertHasActionErrors(['file_path']);

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_successful_filament_upload_creates_a_readable_file_and_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user);
        $product = $this->product();

        Livewire::test(ProductImagesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'file_path' => $this->image(), 'sort_order' => 0, 'is_main' => true,
            ])
            ->assertHasNoActionErrors();

        $image = $product->images()->sole();
        Storage::disk('public')->assertExists($image->file_path);
    }

    public function test_failed_private_upload_is_a_validation_error(): void
    {
        $this->failingDisk('messenger_attachments', true);

        try {
            app(UploadStorage::class)->store(UploadedFile::fake()->create('document.pdf'), '2026/11', 'messenger_attachments', 'attachmentUpload');
            $this->fail('The failed write must not return a path.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attachmentUpload', $exception->errors());
            $this->assertStringContainsString('Не удалось сохранить файл', $exception->errors()['attachmentUpload'][0]);
        }
    }

    public function test_each_upload_disk_can_store_files(): void
    {
        foreach (['public', 'local', 'employee_documents', 'messenger_attachments'] as $name) {
            Storage::fake($name);
            $path = app(UploadStorage::class)->store(UploadedFile::fake()->create('document.pdf'), 'nested/files', $name, 'file');
            Storage::disk($name)->assertExists($path);
        }
    }

    public function test_failed_avatar_replacement_preserves_the_existing_avatar(): void
    {
        $this->failingDisk('public', false);
        Storage::disk('public')->getDriver()->write('customer-avatars/old.png', 'old image');
        $customer = Customer::create(['first_name' => 'Upload test', 'email' => 'upload@example.test', 'password' => 'test-password', 'is_active' => true, 'avatar_path' => 'customer-avatars/old.png']);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.account.avatar.update'), ['avatar' => $this->image()])
            ->assertRedirect()
            ->assertSessionHasErrors('avatar');

        $this->assertSame('customer-avatars/old.png', $customer->fresh()->avatar_path);
        Storage::disk('public')->assertExists('customer-avatars/old.png');
    }

    public function test_failed_messenger_attachment_rolls_back_the_message(): void
    {
        $this->failingDisk('messenger_attachments', false);
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $service = app(MessengerService::class);
        $conversation = $service->findOrCreateDirect($sender, $recipient);
        $before = Message::count();

        try {
            $service->sendMessage($conversation, $sender, 'Upload test', UploadedFile::fake()->create('document.pdf'));
            $this->fail('The failed attachment must not be persisted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attachmentUpload', $exception->errors());
        }

        $this->assertSame($before, Message::count());
        $this->assertSame(0, MessageAttachment::count());
    }
}
