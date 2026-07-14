<x-filament-panels::page>
    @php
        $user = $tasksPage['user'] ?? auth()->user();
        $tabs = $tasksPage['tabs'] ?? [];
        $counts = $tasksPage['counts'] ?? [];
        $columns = $tasksPage['columns'] ?? collect();
        $tasksByColumn = $tasksPage['tasksByColumn'] ?? collect();
        $tasks = $tasksPage['tasks'] ?? collect();
        $selectedTask = $tasksPage['selectedTask'] ?? null;
        $menuGroups = [
            'Главное' => [
                ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => \App\Filament\Pages\Workplace::getUrl()],
                ['label' => 'Календарь', 'icon' => 'calendar', 'url' => \App\Filament\Pages\MyCalendar::getUrl()],
                ['label' => 'Задачи', 'icon' => 'check-square', 'url' => \App\Filament\Pages\Tasks::getUrl(), 'active' => true],
            ],
            'Рабочие инструменты' => [
                ['label' => 'Заявки', 'icon' => 'link', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')],
                ['label' => 'Сообщения', 'icon' => 'message', 'url' => '#', 'badge' => 'Скоро', 'sheet' => 'messages'],
            ],
        ];
    @endphp

    @component('layouts.work', [
        'title' => 'Задачи',
        'subtitle' => 'Личные задачи, поручения руководителя, участники, комментарии и HOLD.',
        'active' => 'tasks',
        'appClass' => 'nik-work-app--tasks nik-tasks',
        'user' => $user,
    ])
        <div
            class="nik-tasks-page"
            x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
            x-on:keydown.escape.window="closeSheet()"
        >
            <section class="nik-tasks-toolbar">
                <div class="nik-tasks-search">
                    <x-work.icon name="search" />
                    <input type="search" placeholder="Поиск по задачам..." wire:model.live.debounce.350ms="search">
                </div>

                <div class="nik-tasks-toolbar-actions">
                    <button type="button" class="nik-tasks-btn is-muted" wire:click="openCreateBoard">
                        <x-work.icon name="grid" />
                        <span>Новая доска</span>
                    </button>
                    <button type="button" class="nik-tasks-btn is-primary" wire:click="openCreateTask">
                        <x-work.icon name="plus" />
                        <span>Создать задачу</span>
                    </button>
                </div>
            </section>

            <section class="nik-tasks-kpis" aria-label="Показатели задач">
                @foreach ($tasksPage['kpis'] ?? [] as $kpi)
                    <article class="nik-tasks-kpi is-{{ $kpi['tone'] }}">
                        <span><x-work.icon :name="$kpi['icon']" /></span>
                        <div>
                            <small>{{ $kpi['label'] }}</small>
                            <strong>{{ $kpi['value'] }}</strong>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="nik-tasks-tabs" aria-label="Разделы задач">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        class="{{ $this->activeTab === $key ? 'is-active' : '' }}"
                        wire:click="setTab('{{ $key }}')"
                    >
                        <span>{{ $label }}</span>
                        <em>{{ $counts[$key] ?? 0 }}</em>
                    </button>
                @endforeach
            </section>

            @if ($this->showCreateTask)
                <section class="nik-tasks-form-panel">
                    <div class="nik-tasks-form-head">
                        <div>
                            <span>Новая задача</span>
                            <h2>Быстрое создание</h2>
                        </div>
                        <button type="button" wire:click="closeCreateTask" aria-label="Закрыть"><x-work.icon name="plus" /></button>
                    </div>

                    <form class="nik-tasks-form-grid" wire:submit.prevent="createTask">
                        <label class="nik-tasks-field is-wide">
                            <span>Название *</span>
                            <input type="text" wire:model="taskForm.title" placeholder="Что нужно сделать?">
                            @error('taskForm.title') <small>{{ $message }}</small> @enderror
                        </label>

                        <label class="nik-tasks-field">
                            <span>Исполнитель</span>
                            <select wire:model="taskForm.assignee_id">
                                @foreach ($tasksPage['assignees'] ?? [] as $assignee)
                                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="nik-tasks-field">
                            <span>Приоритет</span>
                            <select wire:model="taskForm.priority">
                                @foreach ($tasksPage['priorityOptions'] ?? [] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="nik-tasks-field">
                            <span>Срок</span>
                            <input type="datetime-local" wire:model="taskForm.due_at">
                        </label>

                        <label class="nik-tasks-field">
                            <span>SLA, минут</span>
                            <input type="number" min="1" step="1" wire:model="taskForm.sla_minutes" placeholder="Например 240">
                        </label>

                        <label class="nik-tasks-field is-wide">
                            <span>Описание</span>
                            <textarea rows="4" wire:model="taskForm.description" placeholder="Контекст, чеклист или ссылка на материалы"></textarea>
                        </label>

                        <label class="nik-tasks-field is-wide">
                            <span>Участники</span>
                            <select multiple wire:model="taskForm.participant_ids">
                                @foreach ($tasksPage['participantOptions'] ?? [] as $participant)
                                    <option value="{{ $participant->id }}">{{ $participant->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="nik-tasks-form-actions">
                            <button type="button" class="nik-tasks-btn is-muted" wire:click="closeCreateTask">Отмена</button>
                            <button type="submit" class="nik-tasks-btn is-primary">Создать</button>
                        </div>
                    </form>
                </section>
            @endif

            @if ($this->showCreateBoard)
                <section class="nik-tasks-form-panel">
                    <div class="nik-tasks-form-head">
                        <div>
                            <span>Доска</span>
                            <h2>Новая личная доска</h2>
                        </div>
                        <button type="button" wire:click="closeCreateBoard" aria-label="Закрыть"><x-work.icon name="plus" /></button>
                    </div>

                    <form class="nik-tasks-form-grid" wire:submit.prevent="createBoard">
                        <label class="nik-tasks-field">
                            <span>Название *</span>
                            <input type="text" wire:model="boardForm.name" placeholder="Например: Запуск проекта">
                        </label>
                        <label class="nik-tasks-field">
                            <span>Видимость</span>
                            <select wire:model="boardForm.visibility">
                                <option value="private">Личная</option>
                                <option value="shared">По ссылке / участникам</option>
                                <option value="department">Отдел</option>
                                <option value="company">Компания</option>
                            </select>
                        </label>
                        <label class="nik-tasks-field is-wide">
                            <span>Описание</span>
                            <textarea rows="3" wire:model="boardForm.description"></textarea>
                        </label>
                        <div class="nik-tasks-form-actions">
                            <button type="button" class="nik-tasks-btn is-muted" wire:click="closeCreateBoard">Отмена</button>
                            <button type="submit" class="nik-tasks-btn is-primary">Создать доску</button>
                        </div>
                    </form>
                </section>
            @endif

            <div class="nik-tasks-content">
                <main class="nik-tasks-board-wrap">
                    <section class="nik-tasks-board-head">
                        <div>
                            <span>Доска</span>
                            <h2>{{ $tasksPage['selectedBoard']->name ?? 'Моя доска' }}</h2>
                        </div>

                        <div class="nik-tasks-board-switcher">
                            @foreach ($tasksPage['boards'] ?? [] as $board)
                                <button
                                    type="button"
                                    class="{{ (int) $this->boardId === (int) $board->id ? 'is-active' : '' }}"
                                    wire:click="selectBoard({{ $board->id }})"
                                >
                                    {{ $board->name }}
                                </button>
                            @endforeach
                        </div>
                    </section>

                    @if ($this->activeTab === 'archive')
                        <section class="nik-tasks-list">
                            @forelse ($tasks as $task)
                                @include('filament.pages.partials.task-card', ['task' => $task, 'columns' => $columns, 'draggable' => false])
                            @empty
                                <div class="nik-tasks-empty">
                                    <x-work.icon name="check-square" />
                                    <strong>В архиве пока пусто</strong>
                                    <span>Завершённые и архивные задачи появятся здесь.</span>
                                </div>
                            @endforelse
                        </section>
                    @else
                        <section
                            class="nik-tasks-kanban"
                            aria-label="Доска задач"
                            x-data="{
                                draggedTaskId: null,
                                draggedColumnId: null,
                                overColumnId: null,
                                mobileDragActive: false,
                                mobileDragElement: null,
                                dropTask(targetColumnId) {
                                    if (! this.draggedTaskId || ! targetColumnId || Number(this.draggedColumnId) === Number(targetColumnId)) {
                                        this.overColumnId = null;
                                        this.mobileDragActive = false;
                                        this.mobileDragElement = null;
                                        return;
                                    }

                                    this.$wire.moveTask(Number(this.draggedTaskId), Number(targetColumnId));
                                    this.draggedTaskId = null;
                                    this.draggedColumnId = null;
                                    this.overColumnId = null;
                                    this.mobileDragActive = false;
                                    this.mobileDragElement = null;
                                },
                                columnFromPoint(x, y) {
                                    const element = document.elementFromPoint(x, y);
                                    const target = element?.closest('[data-task-column]');

                                    return target?.dataset.taskColumn ?? null;
                                },
                                setMobileOverColumn(x, y) {
                                    this.overColumnId = this.columnFromPoint(x, y);
                                },
                            }"
                            x-bind:class="{ 'is-mobile-dragging': mobileDragActive }"
                        >
                            @foreach ($columns as $column)
                                @php $columnTasks = $tasksByColumn->get($column->id, collect()); @endphp
                                <article
                                    class="nik-tasks-column"
                                    data-task-column="{{ $column->id }}"
                                    style="--task-column-color: {{ $column->color ?: '#2f80ed' }}"
                                    x-bind:class="{ 'is-over': Number(overColumnId) === {{ $column->id }} }"
                                    x-on:dragover.prevent="if (draggedTaskId) overColumnId = '{{ $column->id }}'"
                                    x-on:dragleave="if ($event.currentTarget === $event.target) overColumnId = null"
                                    x-on:drop.prevent="dropTask({{ $column->id }})"
                                >
                                    <header>
                                        <div>
                                            <span></span>
                                            <strong>{{ $column->name }}</strong>
                                        </div>
                                        <em>{{ $columnTasks->count() }}</em>
                                    </header>

                                    <div class="nik-tasks-column-list">
                                        @forelse ($columnTasks as $task)
                                            @include('filament.pages.partials.task-card', ['task' => $task, 'columns' => $columns, 'draggable' => true])
                                        @empty
                                            <div class="nik-tasks-column-empty">Нет задач</div>
                                        @endforelse
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    @endif
                </main>

                <aside class="nik-tasks-side">
                    @if ($selectedTask)
                        <section class="nik-tasks-detail">
                            <div class="nik-tasks-detail-head">
                                <div>
                                    <span>{{ $selectedTask->status_label }}</span>
                                    <h2>{{ $selectedTask->title }}</h2>
                                </div>
                                <button type="button" wire:click="closeTask" aria-label="Закрыть"><x-work.icon name="plus" /></button>
                            </div>

                            <div class="nik-tasks-detail-meta">
                                <span>Исполнитель: <strong>{{ $selectedTask->assignee?->name ?? 'Не назначен' }}</strong></span>
                                <span>Автор: <strong>{{ $selectedTask->creator?->name ?? 'Система' }}</strong></span>
                                <span>Приоритет: <strong>{{ $selectedTask->priority_label }}</strong></span>
                                @if ($selectedTask->due_at)
                                    <span>Срок: <strong>{{ $selectedTask->due_at->format('d.m.Y H:i') }}</strong></span>
                                @endif
                            </div>

                            @if ($selectedTask->description)
                                <p class="nik-tasks-detail-description">{{ $selectedTask->description }}</p>
                            @endif

                            <div class="nik-tasks-detail-actions">
                                @foreach ($selectedTask->board?->columns ?? $columns as $column)
                                    @if ((int) $selectedTask->column_id !== (int) $column->id)
                                        <button type="button" wire:click="moveTask({{ $selectedTask->id }}, {{ $column->id }})">{{ $column->name }}</button>
                                    @endif
                                @endforeach
                                <button type="button" wire:click="archiveTask({{ $selectedTask->id }})">В архив</button>
                            </div>

                            <div class="nik-tasks-hold-box">
                                <label>
                                    <span>{{ $selectedTask->status === \App\Models\Task::STATUS_ON_HOLD ? 'Комментарий снятия HOLD' : 'Причина HOLD' }}</span>
                                    <textarea rows="2" wire:model="holdReason"></textarea>
                                </label>
                                @if ($selectedTask->status === \App\Models\Task::STATUS_ON_HOLD)
                                    <button type="button" wire:click="resumeTask({{ $selectedTask->id }})">Снять HOLD</button>
                                @else
                                    <button type="button" wire:click="putTaskOnHold({{ $selectedTask->id }})">Поставить HOLD</button>
                                @endif
                            </div>

                            <div class="nik-tasks-participants">
                                <h3>Участники</h3>
                                <div>
                                    @forelse ($selectedTask->participants as $participant)
                                        <span>{{ $participant->user?->name }}</span>
                                    @empty
                                        <span>Пока нет дополнительных участников</span>
                                    @endforelse
                                </div>
                                <div class="nik-tasks-add-participant">
                                    <select wire:model="participantUserId">
                                        <option value="">Добавить участника</option>
                                        @foreach ($tasksPage['participantOptions'] ?? [] as $participant)
                                            <option value="{{ $participant->id }}">{{ $participant->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="addParticipant({{ $selectedTask->id }})">Добавить</button>
                                </div>
                            </div>

                            <div class="nik-tasks-comments">
                                <h3>Комментарии</h3>
                                <form wire:submit.prevent="addComment({{ $selectedTask->id }})">
                                    <textarea rows="3" wire:model="commentBody" placeholder="Написать комментарий..."></textarea>
                                    <button type="submit">Отправить</button>
                                </form>

                                <div class="nik-tasks-comment-list">
                                    @forelse ($selectedTask->comments->sortByDesc('created_at') as $comment)
                                        <article class="{{ $comment->is_system ? 'is-system' : '' }}">
                                            <strong>{{ $comment->is_system ? 'Система' : ($comment->user?->name ?? 'Сотрудник') }}</strong>
                                            <span>{{ $comment->created_at?->format('d.m.Y H:i') }}</span>
                                            <p>{{ $comment->body }}</p>
                                        </article>
                                    @empty
                                        <div class="nik-tasks-empty-mini">Комментариев пока нет.</div>
                                    @endforelse
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="nik-tasks-side-empty">
                            <x-work.icon name="check-square" />
                            <strong>Выберите задачу</strong>
                            <span>Здесь появятся участники, комментарии, история и HOLD.</span>
                        </section>
                    @endif
                </aside>
            </div>

            <button type="button" class="nik-tasks-mobile-fab" wire:click="openCreateTask" aria-label="Создать задачу">
                <x-work.icon name="plus" />
            </button>

            <x-work.mobile-bottom-sheets :menu-groups="$menuGroups" />
            <x-work.mobile-bottom-nav active="tasks" />
        </div>
    @endcomponent
</x-filament-panels::page>
