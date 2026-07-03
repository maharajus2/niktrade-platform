<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Дни рождения</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Сегодня, неделя и ближайший месяц.</p>
        </div>

        @php
            $sections = [
                'Сегодня' => $today,
                'На этой неделе' => $week,
                'В этом месяце' => $month,
            ];
        @endphp

        <div class="mt-5 space-y-5">
            @foreach ($sections as $title => $people)
                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $title }}</div>

                    <div class="space-y-2">
                        @forelse ($people as $person)
                            <a href="{{ $person['url'] }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-2.5 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                                @if ($person['avatar_path'])
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($person['avatar_path']) }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        {{ mb_substr($person['name'], 0, 1) }}
                                    </span>
                                @endif

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">{{ $person['name'] }}</span>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $person['department'] ?? 'Без отдела' }} · {{ $person['date']->format('d.m') }} · {{ $person['age'] }}
                                    </span>
                                </span>
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Нет событий.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
