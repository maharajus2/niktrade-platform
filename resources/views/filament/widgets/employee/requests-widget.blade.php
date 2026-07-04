<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">□</span>
                    <span>Мои заявки</span>
                </div>
                <div class="nt-hr-card__subtitle">Отпуск, больничный, выходной и изменения графика.</div>
            </div>

            <a href="{{ $createUrl }}" class="nt-pill">Создать</a>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-kpi-grid">
                @foreach ([
                    'pending' => 'Ожидают',
                    'approved' => 'Одобрены',
                    'rejected' => 'Отклонены',
                    'returned' => 'Возвращены',
                ] as $key => $label)
                    <a href="{{ $indexUrl }}" class="nt-kpi" style="text-decoration: none;">
                        <div class="nt-kpi__label">{{ $label }}</div>
                        <div class="nt-kpi__value">{{ $counts[$key] }}</div>
                    </a>
                @endforeach
            </div>

            <a href="{{ $indexUrl }}" style="display: block; margin-top: 1rem; color: #2563eb; font-size: .88rem; font-weight: 800; text-align: center; text-decoration: none;">
                Открыть все заявки
            </a>
        </div>
    </section>
</x-filament-widgets::widget>
