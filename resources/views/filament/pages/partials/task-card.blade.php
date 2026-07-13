@php
    $priorityTone = match ($task->priority) {
        \App\Models\Task::PRIORITY_URGENT => 'red',
        \App\Models\Task::PRIORITY_HIGH => 'amber',
        \App\Models\Task::PRIORITY_LOW => 'gray',
        default => 'blue',
    };

    $isOverdue = $task->due_at && $task->due_at->isPast() && ! in_array($task->status, [
        \App\Models\Task::STATUS_COMPLETED,
        \App\Models\Task::STATUS_ARCHIVED,
    ], true);
@endphp

<article class="nik-task-card {{ $task->status === \App\Models\Task::STATUS_ON_HOLD ? 'is-hold' : '' }}" wire:key="task-card-{{ $task->id }}">
    <button type="button" class="nik-task-card-main" wire:click="openTask({{ $task->id }})">
        <span class="nik-task-card-priority is-{{ $priorityTone }}">{{ $task->priority_label }}</span>
        <strong>{{ $task->title }}</strong>
        @if ($task->description)
            <small>{{ \Illuminate\Support\Str::limit($task->description, 90) }}</small>
        @endif
    </button>

    <div class="nik-task-card-meta">
        <span>{{ $task->assignee?->greeting_name ?? $task->assignee?->name ?? 'Без исполнителя' }}</span>
        @if ($task->due_at)
            <span class="{{ $isOverdue ? 'is-overdue' : '' }}">{{ $task->due_at->format('d.m H:i') }}</span>
        @endif
    </div>

    <div class="nik-task-card-footer">
        <button type="button" wire:click="openTask({{ $task->id }})">Открыть</button>
        @foreach ($columns as $column)
            @if ((int) $task->column_id !== (int) $column->id)
                <button type="button" wire:click="moveTask({{ $task->id }}, {{ $column->id }})">{{ $column->name }}</button>
            @endif
        @endforeach
    </div>
</article>
