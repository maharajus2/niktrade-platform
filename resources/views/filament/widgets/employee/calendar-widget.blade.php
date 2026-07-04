<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section id="employee-calendar" class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">▣</span>
                    <span>Календарь</span>
                </div>
                <div class="nt-hr-card__subtitle">{{ $monthLabel }}</div>
            </div>

            <a href="{{ $calendarUrl }}" class="nt-pill">Открыть</a>
        </div>

        <div class="nt-hr-card__body">
            <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .35rem;">
                @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)
                    <div style="color: #64748b; font-size: .74rem; font-weight: 800; text-align: center;">{{ $weekday }}</div>
                @endforeach

                @foreach ($days as $day)
                    <div style="min-height: 3.55rem; border: 1px solid {{ $day['isToday'] ? '#60a5fa' : '#e5e7eb' }}; border-radius: 12px; background: {{ $day['isCurrentMonth'] ? '#fff' : '#f8fafc' }}; padding: .45rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: .25rem;">
                            <span style="display: inline-flex; min-width: 1.45rem; height: 1.45rem; align-items: center; justify-content: center; border-radius: 999px; background: {{ $day['isToday'] ? '#2563eb' : 'transparent' }}; color: {{ $day['isToday'] ? '#fff' : ($day['isCurrentMonth'] ? '#0f172a' : '#94a3b8') }}; font-size: .78rem; font-weight: 850;">
                                {{ $day['date']->format('j') }}
                            </span>
                        </div>

                        @if (count($day['events']) > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: .18rem; margin-top: .45rem;">
                                @foreach ($day['events']->take(3) as $event)
                                    <span title="{{ $event->getDisplayTitle() }}" style="width: .35rem; height: .35rem; border-radius: 999px; background: {{ $event->getCalendarColor() }};"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <a href="{{ $calendarUrl }}" style="display: block; margin-top: 1rem; color: #2563eb; font-size: .88rem; font-weight: 800; text-align: center; text-decoration: none;">
                Открыть полный календарь
            </a>
        </div>
    </section>
</x-filament-widgets::widget>
