<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Мои заявки</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Отпуск, больничный, выходной и изменения графика.</p>
            </div>

            <a href="{{ $createUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">Создать заявку</a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <a href="{{ $indexUrl }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-white">
                <div class="text-xs text-gray-500">Ожидают</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $counts['pending'] }}</div>
            </a>
            <a href="{{ $indexUrl }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-white">
                <div class="text-xs text-gray-500">Одобрены</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $counts['approved'] }}</div>
            </a>
            <a href="{{ $indexUrl }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-white">
                <div class="text-xs text-gray-500">Отклонены</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $counts['rejected'] }}</div>
            </a>
            <a href="{{ $indexUrl }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-white">
                <div class="text-xs text-gray-500">Возвращены</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $counts['returned'] }}</div>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
