<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">Требуют внимания</div>
                <div class="nt-hr-card__subtitle">Профили сотрудников с незаполненными HR-данными.</div>
            </div>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-mini-list">
                @forelse ($alerts as $alert)
                    <a href="{{ $alert['url'] }}" class="nt-alert-row">
                        <div style="min-width: 0;">
                            <div class="nt-mini-row__title">{{ $alert['employee'] }}</div>
                            <div class="nt-mini-row__meta">{{ $alert['department'] ?? 'Без отдела' }}</div>
                        </div>

                        <div class="nt-chip-list">
                            @foreach ($alert['issues'] as $issue)
                                <span class="nt-danger-chip">{{ $issue }}</span>
                            @endforeach
                        </div>
                    </a>
                @empty
                    <div class="nt-empty">Критичных HR-полей без заполнения не найдено.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
