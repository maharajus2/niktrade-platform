<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">Быстрые действия</div>
                <div class="nt-hr-card__subtitle">Основные HR-переходы для ежедневной работы.</div>
            </div>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-action-list">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}" class="{{ $action['style'] === 'primary' ? 'nt-action-link nt-action-link--primary' : 'nt-action-link' }}">
                        <span>{{ $action['label'] }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
