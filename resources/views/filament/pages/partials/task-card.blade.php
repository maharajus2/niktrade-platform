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

    $draggable = ($draggable ?? true) && $task->status !== \App\Models\Task::STATUS_ARCHIVED;
@endphp

<article
    class="nik-task-card {{ $task->status === \App\Models\Task::STATUS_ON_HOLD ? 'is-hold' : '' }} {{ $draggable ? 'is-draggable' : '' }}"
    wire:key="task-card-{{ $task->id }}"
    x-data="{
        canDragTask: @js($draggable),
        touchStartX: 0,
        touchStartY: 0,
        touchLastX: 0,
        touchLastY: 0,
        touchLongPressTimer: null,
        touchLongPressReady: false,
        touchMoved: false,
        touchGestureHandled: false,
        isInteractiveTarget(target) {
            return Boolean(target.closest('button, input, select, textarea, a, [role=button], .nik-task-card-footer'));
        },
        startTouchTaskGesture(event) {
            if (! this.canDragTask || event.pointerType !== 'touch' || this.isInteractiveTarget(event.target)) {
                return;
            }

            this.touchStartX = event.clientX;
            this.touchStartY = event.clientY;
            this.touchLastX = event.clientX;
            this.touchLastY = event.clientY;
            this.touchMoved = false;
            this.touchGestureHandled = false;
            this.touchLongPressReady = false;
            window.clearTimeout(this.touchLongPressTimer);

            this.touchLongPressTimer = window.setTimeout(() => {
                this.touchLongPressReady = true;
                this.touchGestureHandled = true;
                mobileDragActive = true;
                mobileDragElement = $el;
                draggedTaskId = '{{ $task->id }}';
                draggedColumnId = '{{ $task->column_id }}';
                overColumnId = '{{ $task->column_id }}';
                $el.classList.add('is-touch-dragging');
                $el.setPointerCapture?.(event.pointerId);
            }, 420);
        },
        moveTouchTaskGesture(event) {
            if (event.pointerType !== 'touch') {
                return;
            }

            this.touchLastX = event.clientX;
            this.touchLastY = event.clientY;

            const deltaX = event.clientX - this.touchStartX;
            const deltaY = event.clientY - this.touchStartY;

            if (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8) {
                this.touchMoved = true;
            }

            if (! this.touchLongPressReady && this.touchMoved && Math.abs(deltaY) > Math.abs(deltaX)) {
                window.clearTimeout(this.touchLongPressTimer);
                return;
            }

            if (this.touchLongPressReady) {
                event.preventDefault();
                setMobileOverColumn(event.clientX, event.clientY);
            }
        },
        finishTouchTaskGesture(event) {
            if (event.pointerType !== 'touch') {
                return;
            }

            window.clearTimeout(this.touchLongPressTimer);

            if (this.touchLongPressReady) {
                event.preventDefault();
                const targetColumnId = columnFromPoint(this.touchLastX, this.touchLastY) ?? overColumnId;
                dropTask(targetColumnId);
                $el.classList.remove('is-touch-dragging');
                this.touchLongPressReady = false;
                return;
            }

            this.touchLongPressReady = false;
            $el.classList.remove('is-touch-dragging');
        },
        cancelTouchTaskGesture() {
            window.clearTimeout(this.touchLongPressTimer);
            this.touchLongPressReady = false;
            mobileDragActive = false;
            if (mobileDragElement === $el) {
                mobileDragElement = null;
            }
            $el.classList.remove('is-touch-dragging');
        },
    }"
    x-on:pointerdown="startTouchTaskGesture($event)"
    x-on:pointermove="moveTouchTaskGesture($event)"
    x-on:pointerup="finishTouchTaskGesture($event)"
    x-on:pointercancel="cancelTouchTaskGesture()"
    x-on:click.capture="if (touchGestureHandled) { $event.preventDefault(); $event.stopPropagation(); touchGestureHandled = false; }"
    @if ($draggable)
        draggable="true"
        x-on:dragstart="
            draggedTaskId = '{{ $task->id }}';
            draggedColumnId = '{{ $task->column_id }}';
            $event.dataTransfer.effectAllowed = 'move';
            $event.dataTransfer.setData('text/plain', '{{ $task->id }}');
        "
        x-on:dragend="
            draggedTaskId = null;
            draggedColumnId = null;
            overColumnId = null;
        "
    @endif
>
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
