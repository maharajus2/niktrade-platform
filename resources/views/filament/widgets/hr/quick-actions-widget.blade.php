<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Быстрые действия</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Основные HR-переходы для ежедневной работы.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($actions as $action)
                    @php
                        $classes = $action['style'] === 'primary'
                            ? 'bg-primary-600 text-white hover:bg-primary-500'
                            : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800';
                    @endphp

                    <a href="{{ $action['url'] }}" class="inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium shadow-sm transition {{ $classes }}">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
