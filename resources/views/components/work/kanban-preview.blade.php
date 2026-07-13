@props([
    'counts' => [
        'new' => 0,
        'in_progress' => 0,
        'review' => 0,
        'done' => 0,
    ],
    'tasks' => collect(),
    'url' => '#',
])

@php
    $columns = [
        ['key' => 'new', 'title' => 'Новые'],
        ['key' => 'in_progress', 'title' => 'В работе'],
        ['key' => 'review', 'title' => 'На проверке'],
        ['key' => 'done', 'title' => 'Готово'],
    ];
@endphp

<div class="nik-work-kanban">
    @foreach ($columns as $column)
        <div class="nik-work-kanban-column">
            <div class="nik-work-kanban-title">
                <span>{{ $column['title'] }}</span>
                <span>{{ $counts[$column['key']] ?? 0 }}</span>
            </div>

            @if ($loop->first)
                <div class="nik-work-task-preview-list">
                    @forelse ($tasks as $task)
                        <a href="{{ $url }}" class="nik-work-task-preview">
                            <strong>{{ $task->title }}</strong>
                            <span>
                                {{ $task->due_at ? $task->due_at->format('d.m H:i') : 'Без срока' }}
                                · {{ $task->priority_label }}
                            </span>
                        </a>
                    @empty
                        <div class="nik-work-task-placeholder">
                            <x-work.icon name="check-square" />
                            <div>
                                Пока нет активных задач.<br>
                                Создайте личную задачу или поручение.
                            </div>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="nik-work-task-placeholder is-compact">
                    <x-work.icon name="check-square" />
                    <div>Откройте доску задач</div>
                </div>
            @endif
        </div>
    @endforeach
</div>
