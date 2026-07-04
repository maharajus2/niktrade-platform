<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">HR: краткий обзор</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Только статусы и сигналы, без рабочих досок и календарей.</p>
            </div>

            <a href="{{ $requestUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Открыть заявки
            </a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($cards as $card)
                @php
                    $badge = match ($card['color']) {
                        'danger' => 'bg-red-100 text-red-700',
                        'warning' => 'bg-yellow-100 text-yellow-700',
                        'success' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-sky-100 text-sky-700',
                    };
                @endphp

                <a href="{{ $card['url'] }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 transition hover:bg-white hover:shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:hover:bg-gray-900">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                    <div class="mt-2 rounded-full px-2 py-1 text-xs font-medium {{ $badge }}">{{ $card['description'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-950 dark:text-white">Сегодня</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700">Отпуск: {{ $todayVacation }}</span>
                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Больничный: {{ $todaySickLeave }}</span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-950 dark:text-white">Дни рождения</div>
                <div class="mt-2 space-y-2">
                    @forelse ($birthdaysToday as $person)
                        <a href="{{ $person['url'] }}" class="block truncate text-sm text-primary-600 hover:text-primary-500">
                            Сегодня: {{ $person['name'] }}
                        </a>
                    @empty
                        <div class="text-sm text-gray-500">Сегодня нет дней рождения.</div>
                    @endforelse

                    @if ($birthdaysWeek !== [])
                        <div class="text-xs text-gray-500">На неделе: {{ collect($birthdaysWeek)->pluck('name')->join(', ') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
