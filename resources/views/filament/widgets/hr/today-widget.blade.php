<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">Сегодня</div>
                <div class="nt-hr-card__subtitle">{{ $dateLabel }}</div>
            </div>

            <a href="{{ $calendarUrl }}" class="nt-pill">Открыть календарь</a>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-kpi-grid">
                @foreach ($stats as $stat)
                    <div class="nt-kpi">
                        <div class="nt-kpi__label">{{ $stat['label'] }}</div>
                        <div class="nt-kpi__value">{{ $stat['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 1rem;">
                @forelse ($events as $event)
                    <a href="{{ $event['url'] ?? '#' }}" class="nt-mini-row" style="text-decoration: none;">
                        <div class="nt-mini-row__dot" style="background: {{ $event['color'] }}; box-shadow: 0 0 0 5px {{ $event['color'] }}22;"></div>
                        <div style="min-width: 0;">
                            <div class="nt-mini-row__title">{{ $event['employee'] }}</div>
                            <div class="nt-mini-row__meta">{{ $event['title'] }}</div>
                            @if ($event['department'])
                                <div class="nt-mini-row__meta">{{ $event['department'] }}</div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="nt-empty">На сегодня нет кадровых событий в календаре.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
