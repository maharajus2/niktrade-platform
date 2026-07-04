@php
    $columns = [
        [
            'title' => 'Новые',
            'count' => 2,
            'items' => [
                ['title' => 'Подготовить отчёт', 'meta' => 'До 28.11', 'tone' => 'red'],
                ['title' => 'Обновить инструкцию', 'meta' => 'До 29.11', 'tone' => 'red'],
            ],
            'footer' => '+ Добавить задачу',
        ],
        [
            'title' => 'В работе',
            'count' => 1,
            'items' => [
                ['title' => 'Проверить документы', 'meta' => 'До 30.11', 'tone' => 'amber'],
            ],
            'footer' => '+ Добавить задачу',
        ],
        [
            'title' => 'На проверке',
            'count' => 1,
            'items' => [
                ['title' => 'Согласовать договор', 'meta' => 'До 26.11', 'tone' => 'green'],
            ],
            'footer' => '+ Добавить задачу',
        ],
        [
            'title' => 'Готово',
            'count' => 2,
            'items' => [
                ['title' => 'Отчёт за неделю', 'meta' => '25.11', 'tone' => 'green', 'done' => true],
                ['title' => 'План на месяц', 'meta' => '24.11', 'tone' => 'green', 'done' => true],
            ],
            'footer' => '+ Показать все',
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

            @foreach ($column['items'] as $item)
                <div class="nik-work-task-card">
                    <div>
                        <div class="nik-work-row-title">{{ $item['title'] }}</div>
                        <div class="nik-work-task-meta is-{{ $item['tone'] }}">{{ $item['meta'] }}</div>
                    </div>

                    @if ($item['done'] ?? false)
                        <span class="nik-work-task-check" aria-hidden="true">✓</span>
                    @endif
                </div>
            @endforeach

            <div class="nik-work-kanban-footer">{{ $column['footer'] }}</div>
        </div>
    @endforeach
</div>
