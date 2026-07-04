<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Мои задачи</h2>
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500">Скоро</span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            @foreach (['Новые', 'В работе', 'На проверке', 'Готово'] as $column)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div class="text-sm font-medium text-gray-950">{{ $column }}</div>
                    <div class="mt-2 text-xs text-gray-500">Модуль задач будет подключён позже.</div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            @foreach (['Встречи', 'Почта', 'Заметки', 'Тайм-трекинг'] as $item)
                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500">{{ $item }}</span>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
