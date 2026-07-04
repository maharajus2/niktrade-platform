<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">Ближайшие события</div>
                <div class="nt-hr-card__subtitle">Напоминания HR на 7 дней вперед.</div>
            </div>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-mini-list">
                @forelse ($events as $event)
                    <a href="{{ $event['url'] ?? '#' }}" class="nt-mini-row" style="text-decoration: none;">
                        <div style="min-width: 0;">
                            <div class="nt-mini-row__title">{{ $event['label'] }}</div>
                            <div class="nt-mini-row__meta">{{ $event['description'] }}</div>
                        </div>

                        <span class="nt-pill">{{ $event['date']->format('d.m') }}</span>
                    </a>
                @empty
                    <div class="nt-empty">На ближайшую неделю нет срочных HR-событий.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
