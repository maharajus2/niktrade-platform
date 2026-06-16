<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        @livewire(
            \App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager::class,
            [
                'ownerRecord' => $this->record,
                'pageClass' => \App\Filament\Resources\Products\Pages\EditProduct::class,
            ],
            key('product-images-relation-manager-' . $this->record->getKey())
        )

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
