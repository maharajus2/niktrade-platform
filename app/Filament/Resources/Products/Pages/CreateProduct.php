<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Products\ProductTemplateService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    #[Locked]
    public ?int $sourceProductId = null;

    protected bool $shouldCopySourceImages = false;

    public function hasDatabaseTransactions(): bool
    {
        return true;
    }

    public function mount(): void
    {
        abort_unless(ProductResource::canCreate(), 403);

        if ($sourceId = request()->integer('source')) {
            $source = Product::query()->findOrFail($sourceId);

            abort_unless(ProductResource::canView($source), 403);

            $this->sourceProductId = $source->getKey();
        }

        parent::mount();
    }

    public function hasSourceProduct(): bool
    {
        return $this->sourceProductId !== null;
    }

    public function getSourceProduct(): ?Product
    {
        if (! $this->sourceProductId) {
            return null;
        }

        return Product::query()->find($this->sourceProductId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateBannerData(): array
    {
        $source = $this->getSourceProduct();

        return [
            'sourceProduct' => $source,
            'sourceUrl' => $source ? ProductResource::getUrl('view', ['record' => $source]) : null,
            'clearUrl' => ProductResource::getUrl('create'),
        ];
    }

    protected function fillForm(): void
    {
        if (! $source = $this->getSourceProduct()) {
            parent::fillForm();

            return;
        }

        $this->callHook('beforeFill');
        $this->form->fill(app(ProductTemplateService::class)->getTemplateData($source));
        $this->callHook('afterFill');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->shouldCopySourceImages = $this->hasSourceProduct()
            && (bool) ($data['copy_source_images'] ?? false);

        unset($data['copy_source_images']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = parent::handleRecordCreation($data);
        $source = $this->getSourceProduct();

        if ($source && $this->shouldCopySourceImages) {
            app(ProductTemplateService::class)->copyImages($source, $product);
        }

        return $product;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        if ($source = $this->getSourceProduct()) {
            return "Товар создан на основе «{$source->name}».";
        }

        return parent::getCreatedNotificationTitle();
    }
}
