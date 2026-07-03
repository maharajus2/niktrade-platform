<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            @php
                $classes = match ($card['color']) {
                    'danger' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300',
                    'warning' => 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-950/30 dark:text-yellow-300',
                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
                    default => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-300',
                };
            @endphp

            <a href="{{ $card['url'] }}" class="rounded-xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                        <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                    </div>

                    <span class="rounded-full border px-2.5 py-1 text-xs font-medium {{ $classes }}">
                        HR
                    </span>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">{{ $card['description'] }}</div>
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
