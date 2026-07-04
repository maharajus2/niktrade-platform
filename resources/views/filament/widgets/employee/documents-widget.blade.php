<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section id="employee-documents" class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">▤</span>
                    <span>Мои документы</span>
                </div>
                <div class="nt-hr-card__subtitle">Комплектность и сроки ваших документов.</div>
            </div>

            <span class="nt-pill">{{ $completeness }}%</span>
        </div>

        <div class="nt-hr-card__body">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: .75rem;">
                <div style="color: #64748b; font-size: .84rem; font-weight: 800;">Комплектность</div>
                <div style="color: #0f172a; font-size: .9rem; font-weight: 850;">{{ $completeness }}%</div>
            </div>

            <div class="nt-progress" style="margin-top: .65rem;">
                <div class="nt-progress__bar" style="width: {{ $completeness }}%;"></div>
            </div>

            <div class="nt-kpi-grid" style="margin-top: 1rem;">
                <div class="nt-kpi">
                    <div class="nt-kpi__label">Не хватает</div>
                    <div class="nt-kpi__value">{{ $missingCount }}</div>
                </div>
                <div class="nt-kpi">
                    <div class="nt-kpi__label">Истекают</div>
                    <div class="nt-kpi__value">{{ $expiringCount }}</div>
                </div>
                <div class="nt-kpi">
                    <div class="nt-kpi__label">Просрочены</div>
                    <div class="nt-kpi__value">{{ $expiredCount }}</div>
                </div>
            </div>

            <a href="#employee-documents" style="display: block; margin-top: 1rem; color: #2563eb; font-size: .88rem; font-weight: 800; text-align: center; text-decoration: none;">
                Открыть документы
            </a>
        </div>
    </section>
</x-filament-widgets::widget>
