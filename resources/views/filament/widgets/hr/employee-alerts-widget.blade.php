<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Требуют внимания</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Профили сотрудников с незаполненными HR-данными.</p>
        </div>

        <div class="mt-5 space-y-3">
            @forelse ($alerts as $alert)
                <a href="{{ $alert['url'] }}" class="block rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $alert['employee'] }}</div>
                            <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $alert['department'] ?? 'Без отдела' }}</div>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($alert['issues'] as $issue)
                                <span class="rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">{{ $issue }}</span>
                            @endforeach
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Критичных HR-полей без заполнения не найдено.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
