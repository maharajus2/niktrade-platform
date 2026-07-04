<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <section class="nt-hr-card">
        <div class="nt-hr-card__header">
            <div>
                <div class="nt-hr-card__title">
                    <span aria-hidden="true">☑</span>
                    <span>Мои задачи</span>
                </div>
                <div class="nt-hr-card__subtitle">Компактная доска задач появится после подключения модуля.</div>
            </div>

            <span class="nt-pill">Скоро</span>
        </div>

        <div class="nt-hr-card__body">
            <div class="nt-task-board">
                @foreach (['Новые', 'В работе', 'На проверке', 'Готово'] as $column)
                    <div style="min-height: 8rem; border: 1px solid #e5e7eb; border-radius: 14px; background: #f8fafc; padding: .85rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: .5rem;">
                            <div style="color: #0f172a; font-size: .9rem; font-weight: 850;">{{ $column }}</div>
                            <span style="border-radius: 999px; background: #e2e8f0; padding: .12rem .45rem; color: #64748b; font-size: .72rem; font-weight: 800;">0</span>
                        </div>

                        <div style="margin-top: .8rem; border: 1px dashed #cbd5e1; border-radius: 12px; background: #fff; padding: .75rem; color: #94a3b8; font-size: .82rem;">
                            Модуль задач будет подключён позже.
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
