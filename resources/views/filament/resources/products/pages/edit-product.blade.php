<x-filament-panels::page>
    <form id="product-edit-form" wire:submit="save">
        {{ $this->form }}
    </form>

    <x-filament-panels::resources.relation-managers
        :active-locale="property_exists($this, 'activeLocale') ? $this->activeLocale : null"
        :active-manager="$this->activeRelationManager ?? null"
        :content-tab-icon="method_exists($this, 'getContentTabIcon') ? $this->getContentTabIcon() : null"
        :content-tab-label="method_exists($this, 'getContentTabLabel') ? $this->getContentTabLabel() : null"
        :content-tab-position="method_exists($this, 'getContentTabPosition') ? $this->getContentTabPosition() : null"
        :managers="$this->getRelationManagers()"
        :owner-record="$record"
        :page-class="static::class"
    />

    <div class="flex items-center gap-3">
        <x-filament::button type="submit" form="product-edit-form">
            Сохранить
        </x-filament::button>

        <x-filament::button color="gray" tag="a" :href="static::getResource()::getUrl('index')">
            Отмена
        </x-filament::button>
    </div>
</x-filament-panels::page>
