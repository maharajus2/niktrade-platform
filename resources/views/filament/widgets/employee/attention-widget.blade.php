<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Требует внимания</h2>

        <div class="mt-5 space-y-3">
            @forelse ($items as $item)
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-700">{{ $item }}</div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
                    На сегодня нет важных уведомлений.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
