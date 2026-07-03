<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Сегодня</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $dateLabel }}</p>
            </div>

            <a href="{{ $calendarUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Открыть календарь
            </a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            @foreach ($stats as $stat)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 space-y-3">
            @forelse ($events as $event)
                <a href="{{ $event['url'] ?? '#' }}" class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $event['color'] }}"></span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">{{ $event['employee'] }}</span>
                        <span class="block text-sm text-gray-600 dark:text-gray-300">{{ $event['title'] }}</span>
                        @if ($event['department'])
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $event['department'] }}</span>
                        @endif
                    </span>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    На сегодня нет кадровых событий в календаре.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
