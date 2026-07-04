<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">+</span>
                    <span>Быстрые действия</span>
                </div>
            </div>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-action-list">
                @foreach ($actions as $action)
                    @if ($action['disabled'])
                        <span title="Будет доступно позже." class="nt-action-disabled">
                            <span>{{ $action['label'] }}</span>
                            <span aria-hidden="true">→</span>
                        </span>
                    @else
                        <a href="{{ $action['url'] }}" class="nt-action-link">
                            <span>{{ $action['label'] }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
