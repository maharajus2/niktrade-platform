<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Мой день</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $dateLabel }}</p>
            </div>

            <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700">{{ $statusLabel }}</span>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-xs text-gray-500 dark:text-gray-400">Сегодняшняя смена</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ $shiftLabel ?? 'Сегодня смен не назначено.' }}
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-xs text-gray-500 dark:text-gray-400">Часы сегодня</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($hoursToday, 1, ',', ' ') }}</div>
            </div>
        </div>

        <div class="mt-5 space-y-3">
            @forelse ($timeline as $item)
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $item['time'] }}</div>
                    <div class="min-w-0 text-sm text-gray-950 dark:text-white">{{ $item['title'] }}</div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    На сегодня нет событий.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
