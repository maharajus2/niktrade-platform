@props([
    'columns' => ['Новые', 'В работе', 'На проверке', 'Готово'],
])

<div class="nik-work-kanban">
    @foreach ($columns as $column)
        <div class="nik-work-kanban-column">
            <div class="nik-work-kanban-title">
                <span>{{ $column }}</span>
                <x-work.badge tone="gray">0</x-work.badge>
            </div>

            <div class="nik-work-placeholder-box">
                Модуль задач будет подключён позже.
            </div>
        </div>
    @endforeach
</div>
