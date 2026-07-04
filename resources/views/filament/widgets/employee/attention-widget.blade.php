<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">!</span>
                    <span>Требует внимания</span>
                </div>
            </div>

            @if (count($items) > 0)
                <span class="nt-pill" style="background: #fee2e2; color: #dc2626;">{{ count($items) }}</span>
            @endif
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-mini-list">
                @forelse ($items as $item)
                    <div class="nt-mini-row" style="border-color: #fed7aa; background: #fff7ed;">
                        <div class="nt-mini-row__dot" style="background: #f97316; box-shadow: 0 0 0 5px rgba(249, 115, 22, .14);"></div>
                        <div class="nt-mini-row__title">{{ $item }}</div>
                    </div>
                @empty
                    <div class="nt-empty">На сегодня нет важных уведомлений.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
