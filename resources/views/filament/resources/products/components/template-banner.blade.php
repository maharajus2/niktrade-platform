@if ($sourceProduct)
    <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-950">
        <p class="text-sm text-gray-700 dark:text-gray-200">
            Новый товар создаётся на основе:
            <span class="font-semibold">{{ $sourceProduct->name }}</span>
        </p>

        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            <x-filament::link :href="$sourceUrl" icon="heroicon-o-arrow-top-right-on-square">
                Открыть исходный товар
            </x-filament::link>

            <x-filament::link :href="$clearUrl" color="gray" icon="heroicon-o-x-mark">
                Очистить шаблон
            </x-filament::link>
        </div>
    </div>
@endif
