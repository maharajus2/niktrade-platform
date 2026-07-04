@props([
    'monthLabel',
    'days',
    'url' => null,
])

<div style="text-align: center; margin-bottom: 18px; font-size: 16px; font-weight: 900;">{{ $monthLabel }}</div>

<div class="nik-work-calendar-head">
    @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)
        <div class="nik-work-calendar-weekday">{{ $weekday }}</div>
    @endforeach
</div>

<div class="nik-work-calendar-grid">
    @foreach ($days as $day)
        <div class="nik-work-day-cell {{ $day['isCurrentMonth'] ? '' : 'is-muted' }} {{ $day['isToday'] ? 'is-today' : '' }}">
            <span>{{ $day['date']->format('j') }}</span>

            @if (count($day['events']) > 0)
                <span class="nik-work-event-dots">
                    @foreach ($day['events'] as $event)
                        <span class="nik-work-event-dot" style="background: {{ $event->getCalendarColor() }}"></span>
                    @endforeach
                </span>
            @endif
        </div>
    @endforeach
</div>

@if ($url)
    <a href="{{ $url }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
        Открыть полный календарь
    </a>
@endif
