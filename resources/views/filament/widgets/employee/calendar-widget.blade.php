<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div id="employee-calendar" class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Мой календарь</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $monthLabel }}</p>
            </div>

            <a href="{{ $calendarUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">Открыть календарь</a>
        </div>

        <div class="mt-5 grid" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .375rem;">
            @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)
                <div class="text-xs font-medium text-gray-500">{{ $weekday }}</div>
            @endforeach

            @foreach ($days as $day)
                <div class="rounded-lg border p-2 {{ $day['isCurrentMonth'] ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50' }}" style="min-height: 76px;">
                    <div class="text-xs font-medium {{ $day['isToday'] ? 'text-primary-600' : 'text-gray-500' }}">{{ $day['date']->format('j') }}</div>

                    <div class="mt-1 space-y-2">
                        @foreach ($day['events'] as $event)
                            <div class="truncate rounded-full px-2 py-1 text-xs font-medium" style="background: {{ $event->getCalendarColor() }}22; color: {{ $event->getCalendarColor() }};">
                                {{ $event->getDisplayTitle() }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
