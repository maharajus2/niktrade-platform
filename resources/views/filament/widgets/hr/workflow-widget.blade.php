<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">На согласовании</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Заявки на график и кадровые отсутствия.</p>
            </div>

            <a href="{{ $url }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">Открыть</a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($items as $item)
                <a href="{{ $url }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 transition hover:bg-white hover:shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:hover:bg-gray-900">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $item['value'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
