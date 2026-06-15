<x-filament-panels::page>
    <form id="product-edit-form" wire:submit="save">
        {{ $this->form }}
    </form>

    @livewire(
        \App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager::class,
        [
            'ownerRecord' => $this->record,
            'pageClass' => \App\Filament\Resources\Products\Pages\EditProduct::class,
        ],
        key('product-images-relation-manager-' . $this->record->getKey())
    )

    <div class="flex items-center gap-3">
        <x-filament::button type="submit" form="product-edit-form">
            Сохранить
        </x-filament::button>

        <x-filament::button color="gray" tag="a" :href="\App\Filament\Resources\Products\ProductResource::getUrl('index')">
            Отмена
        </x-filament::button>
    </div>
</x-filament-panels::page>
