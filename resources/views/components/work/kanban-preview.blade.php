@php
    $columns = [
        [
            'title' => 'Новые',
            'count' => 0,
        ],
        [
            'title' => 'В работе',
            'count' => 0,
        ],
        [
            'title' => 'На проверке',
            'count' => 0,
        ],
        [
            'title' => 'Готово',
            'count' => 0,
        ],
    ];
@endphp

<div class="nik-work-kanban">
    @foreach ($columns as $column)
        <div class="nik-work-kanban-column">
            <div class="nik-work-kanban-title">
                <span>{{ $column['title'] }}</span>
                <span>{{ $column['count'] }}</span>
            </div>

            <div class="nik-work-task-placeholder">
                <x-work.icon name="check-square" />
                <div>
                    Модуль задач будет<br>
                    подключён позже.
                </div>
            </div>
        </div>
    @endforeach
</div>
