<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Ближайшие события</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Напоминания HR на 7 дней вперед.</p>
        </div>

        <div class="mt-5 space-y-3">
            @forelse ($events as $event)
                @php
                    $badge = match ($event['color']) {
                        'danger' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                        'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
                        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
                        default => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
                    };
                @endphp

                <a href="{{ $event['url'] ?? '#' }}" class="block rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $event['label'] }}</div>
                            <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $event['description'] }}</div>
                        </div>

                        <span class="shrink-0 rounded-full px-2 py-1 text-xs font-medium {{ $badge }}">
                            {{ $event['date']->format('d.m') }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    На ближайшую неделю нет срочных HR-событий.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
