<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">На согласовании</div>
                <div class="nt-hr-card__subtitle">Заявки на график и кадровые отсутствия.</div>
            </div>

            <a href="{{ $url }}" class="nt-pill">Открыть</a>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-workflow-grid">
                @foreach ($items as $item)
                    <a href="{{ $url }}" class="nt-kpi" style="text-decoration: none;">
                        <div class="nt-kpi__label">{{ $item['label'] }}</div>
                        <div class="nt-kpi__value">{{ $item['value'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
