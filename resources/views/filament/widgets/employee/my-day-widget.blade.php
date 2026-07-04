<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">◷</span>
                    <span>Мой день</span>
                </div>
                <div class="nt-hr-card__subtitle">{{ $dateLabel }}</div>
            </div>

            <span class="nt-pill">{{ $statusLabel }}</span>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-my-day-grid">
                <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: linear-gradient(180deg, #f8fafc, #fff); padding: 1rem;">
                    <div style="color: #64748b; font-size: .8rem; font-weight: 700;">Ваша смена</div>
                    <div style="margin-top: .45rem; color: #0f172a; font-size: 1.55rem; font-weight: 850; line-height: 1.1;">
                        {{ $shiftLabel ?? 'Не назначена' }}
                    </div>

                    @if ($shiftLabel)
                        <div style="display: inline-flex; margin-top: .7rem; border-radius: 999px; background: #dcfce7; padding: .35rem .65rem; color: #15803d; font-size: .8rem; font-weight: 800;">
                            Сегодня
                        </div>
                    @else
                        <div style="margin-top: .7rem; color: #64748b; font-size: .86rem;">
                            Сегодня смен не назначено.
                        </div>
                    @endif

                    <div style="margin-top: 1.25rem; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                        <div style="color: #64748b; font-size: .8rem; font-weight: 700;">Рабочее время сегодня</div>
                        <div style="margin-top: .35rem; color: #0f172a; font-size: 1.2rem; font-weight: 850;">
                            {{ number_format($hoursToday, 1, ',', ' ') }} ч
                        </div>
                        <div class="nt-progress" style="margin-top: .75rem;">
                            <div class="nt-progress__bar" style="width: {{ min(100, ($hoursToday / 8) * 100) }}%;"></div>
                        </div>
                    </div>
                </div>

                <div class="nt-mini-list">
                    @forelse ($timeline as $index => $item)
                        <div class="nt-mini-row" style="border-color: {{ $index === 0 ? '#bfdbfe' : '#e5e7eb' }};">
                            <div style="width: 3.25rem; flex: 0 0 auto; color: #0f172a; font-size: .82rem; font-weight: 850;">
                                {{ $item['time'] }}
                            </div>
                            <div class="nt-mini-row__dot" style="background: {{ $index === 0 ? '#22c55e' : '#3b82f6' }};"></div>
                            <div style="min-width: 0;">
                                <div class="nt-mini-row__title">{{ $item['title'] }}</div>
                                <div class="nt-mini-row__meta">Событие рабочего дня</div>
                            </div>
                        </div>
                    @empty
                        <div class="nt-empty">На сегодня нет событий в календаре.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
