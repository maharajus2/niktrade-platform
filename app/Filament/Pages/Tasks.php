<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\User;
use App\Services\Tasks\TaskAccessService;
use App\Services\Tasks\TaskBoardService;
use App\Services\Tasks\TaskEngineService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class Tasks extends Page
{
    protected static string $routePath = '/tasks';

    protected string $view = 'filament.pages.tasks';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Задачи';

    protected static ?string $navigationLabel = 'Задачи';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    public string $activeTab = 'my';

    public ?int $boardId = null;

    public string $search = '';

    public bool $showCreateTask = false;

    public bool $showCreateBoard = false;

    public ?int $selectedTaskId = null;

    /**
     * @var array<string, mixed>
     */
    public array $taskForm = [
        'title' => '',
        'description' => '',
        'assignee_id' => null,
        'priority' => Task::PRIORITY_NORMAL,
        'due_at' => null,
        'sla_minutes' => null,
        'participant_ids' => [],
    ];

    /**
     * @var array<string, mixed>
     */
    public array $boardForm = [
        'name' => '',
        'description' => '',
        'visibility' => TaskBoard::VISIBILITY_PRIVATE,
    ];

    public string $commentBody = '';

    public string $holdReason = '';

    public ?int $participantUserId = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User;
    }

    public function mount(TaskBoardService $boards): void
    {
        $user = auth()->user();

        if ($user instanceof User) {
            $this->boardId = $this->boardId ?: $boards->defaultBoardFor($user)->id;
            $this->taskForm['assignee_id'] = $this->taskForm['assignee_id'] ?: $user->id;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Задачи';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'tasksPage' => $this->tasksPageData(),
        ];
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['my', 'personal', 'from_manager', 'assigned_by_me', 'participating', 'boards', 'archive'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->selectedTaskId = null;
    }

    public function selectBoard(int $boardId): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $board = TaskBoard::query()->find($boardId);

        if ($board instanceof TaskBoard && app(TaskAccessService::class)->canViewBoard($board, $user)) {
            $this->boardId = $board->id;
            $this->activeTab = 'boards';
        }
    }

    public function openCreateTask(): void
    {
        $this->showCreateTask = true;
    }

    public function closeCreateTask(): void
    {
        $this->showCreateTask = false;
    }

    public function openCreateBoard(): void
    {
        $this->showCreateBoard = true;
    }

    public function closeCreateBoard(): void
    {
        $this->showCreateBoard = false;
    }

    public function createTask(TaskEngineService $engine): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $data = Validator::make($this->taskForm, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'due_at' => ['nullable', 'date'],
            'sla_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ])->validate();

        try {
            $data['board_id'] = $this->boardId;
            $task = $engine->createTask($user, $data);
            $this->selectedTaskId = $task->id;
            $this->showCreateTask = false;
            $this->taskForm = [
                'title' => '',
                'description' => '',
                'assignee_id' => $user->id,
                'priority' => Task::PRIORITY_NORMAL,
                'due_at' => null,
                'sla_minutes' => null,
                'participant_ids' => [],
            ];

            Notification::make()->title('Задача создана')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось создать задачу')->body($exception->getMessage())->danger()->send();
        }
    }

    public function createBoard(TaskBoardService $boards): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $data = Validator::make($this->boardForm, [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'visibility' => ['required', 'string', 'in:private,shared,department,company'],
        ])->validate();

        try {
            $board = $boards->createPersonalBoard($user, $user, $data);
            $this->boardId = $board->id;
            $this->activeTab = 'boards';
            $this->showCreateBoard = false;
            $this->boardForm = [
                'name' => '',
                'description' => '',
                'visibility' => TaskBoard::VISIBILITY_PRIVATE,
            ];

            Notification::make()->title('Доска создана')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось создать доску')->body($exception->getMessage())->danger()->send();
        }
    }

    public function openTask(int $taskId): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $task = $this->visibleTaskQuery($user)
            ->with(['board', 'column', 'creator', 'assignee', 'assignedBy', 'participants.user', 'comments.user', 'activityLogs.actor'])
            ->find($taskId);

        if (! $task instanceof Task || ! app(TaskAccessService::class)->canView($task, $user)) {
            Notification::make()->title('Задача недоступна')->danger()->send();

            return;
        }

        $this->selectedTaskId = $task->id;
    }

    public function closeTask(): void
    {
        $this->selectedTaskId = null;
        $this->commentBody = '';
        $this->holdReason = '';
        $this->participantUserId = null;
    }

    public function moveTask(int $taskId, int $columnId, TaskEngineService $engine): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $task = $this->visibleTaskQuery($user)->findOrFail($taskId);
            $column = TaskColumn::query()->findOrFail($columnId);
            $engine->moveToColumn($task, $column, $user);
            $this->selectedTaskId = $taskId;
            Notification::make()->title('Задача перемещена')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Не удалось переместить задачу')->body($exception->getMessage())->danger()->send();
        }
    }

    public function completeTask(int $taskId, TaskEngineService $engine): void
    {
        $this->runTaskAction($taskId, fn (Task $task, User $user) => $engine->complete($task, $user), 'Задача завершена');
    }

    public function archiveTask(int $taskId, TaskEngineService $engine): void
    {
        $this->runTaskAction($taskId, fn (Task $task, User $user) => $engine->archive($task, $user), 'Задача отправлена в архив');
        $this->selectedTaskId = null;
    }

    public function putTaskOnHold(int $taskId, TaskEngineService $engine): void
    {
        $reason = trim($this->holdReason);

        $this->runTaskAction($taskId, fn (Task $task, User $user) => $engine->putOnHold($task, $user, $reason), 'Задача поставлена на HOLD');
        $this->holdReason = '';
    }

    public function resumeTask(int $taskId, TaskEngineService $engine): void
    {
        $comment = trim($this->holdReason);

        $this->runTaskAction($taskId, fn (Task $task, User $user) => $engine->resume($task, $user, $comment), 'Задача возвращена в работу');
        $this->holdReason = '';
    }

    public function addComment(int $taskId, TaskEngineService $engine): void
    {
        $body = trim($this->commentBody);

        $this->runTaskAction($taskId, fn (Task $task, User $user) => $engine->addComment($task, $user, $body), 'Комментарий добавлен');
        $this->commentBody = '';
    }

    public function addParticipant(int $taskId, TaskEngineService $engine): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $this->participantUserId) {
            return;
        }

        $participant = User::query()->find($this->participantUserId);

        if (! $participant instanceof User) {
            return;
        }

        $this->runTaskAction($taskId, fn (Task $task, User $actor) => $engine->addParticipant($task, $actor, $participant), 'Участник добавлен');
        $this->participantUserId = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function tasksPageData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $access = app(TaskAccessService::class);
        $defaultBoard = app(TaskBoardService::class)->defaultBoardFor($user);
        $boards = $this->visibleBoards($user, $access);
        $selectedBoard = $boards->firstWhere('id', $this->boardId) ?: $defaultBoard;
        $this->boardId = $selectedBoard->id;

        $baseQuery = $this->visibleTaskQuery($user)
            ->with(['board', 'column', 'creator', 'assignee', 'assignedBy', 'participants.user'])
            ->when($this->activeTab !== 'archive', fn (Builder $query): Builder => $query->active())
            ->when($this->activeTab === 'archive', fn (Builder $query): Builder => $query->archived())
            ->when(trim($this->search) !== '', function (Builder $query): Builder {
                $search = trim($this->search);

                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            });

        $tasks = (clone $baseQuery)
            ->when($this->activeTab === 'my', fn (Builder $query): Builder => $query->where('assignee_id', $user->id))
            ->when($this->activeTab === 'personal', fn (Builder $query): Builder => $query->where('creator_id', $user->id)->where('assignee_id', $user->id)->where('type', Task::TYPE_PERSONAL))
            ->when($this->activeTab === 'from_manager', fn (Builder $query): Builder => $query->where('assignee_id', $user->id)->whereNotNull('assigned_by_id')->where('assigned_by_id', '!=', $user->id))
            ->when($this->activeTab === 'assigned_by_me', fn (Builder $query): Builder => $query->where('assigned_by_id', $user->id))
            ->when($this->activeTab === 'participating', fn (Builder $query): Builder => $query->whereHas('participants', fn (Builder $query): Builder => $query->where('user_id', $user->id)))
            ->when($this->activeTab === 'boards', fn (Builder $query): Builder => $query->where('board_id', $selectedBoard->id))
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderByRaw('due_at nulls last')
            ->latest()
            ->get();

        $selectedTask = $this->selectedTaskId
            ? $this->visibleTaskQuery($user)
                ->with(['board.columns', 'column', 'creator', 'assignee', 'assignedBy', 'participants.user', 'comments.user', 'holds.startedBy', 'activityLogs.actor'])
                ->find($this->selectedTaskId)
            : null;

        return [
            'user' => $user,
            'boards' => $boards,
            'selectedBoard' => $selectedBoard->loadMissing('columns'),
            'columns' => $selectedBoard->columns,
            'tasks' => $tasks,
            'tasksByColumn' => $tasks->groupBy('column_id'),
            'selectedTask' => $selectedTask,
            'assignees' => $access->possibleAssignees($user),
            'participantOptions' => User::query()->whereNull('archived_at')->orderBy('name')->limit(80)->get(),
            'counts' => $this->tabCounts($user),
            'kpis' => $this->kpis($user),
            'priorityOptions' => Task::priorityOptions(),
            'statusOptions' => Task::statusOptions(),
            'tabs' => [
                'my' => 'Мои задачи',
                'personal' => 'Личные',
                'from_manager' => 'От руководителя',
                'assigned_by_me' => 'Назначенные мной',
                'participating' => 'Участвую',
                'boards' => 'Доски',
                'archive' => 'Архив',
            ],
        ];
    }

    /**
     * @return Collection<int, TaskBoard>
     */
    private function visibleBoards(User $user, TaskAccessService $access): Collection
    {
        return TaskBoard::query()
            ->with('columns')
            ->active()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('owner_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('visibility', TaskBoard::VISIBILITY_COMPANY);

                if ($user->department_id !== null) {
                    $query->orWhere(function (Builder $query) use ($user): void {
                        $query
                            ->where('visibility', TaskBoard::VISIBILITY_DEPARTMENT)
                            ->where('department_id', $user->department_id);
                    });
                }
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->filter(fn (TaskBoard $board): bool => $access->canViewBoard($board, $user))
            ->values();
    }

    private function visibleTaskQuery(User $user): Builder
    {
        return app(TaskAccessService::class)->scopeVisibleTasks(Task::query(), $user);
    }

    /**
     * @return array<string, int>
     */
    private function tabCounts(User $user): array
    {
        $active = $this->visibleTaskQuery($user)->active();

        return [
            'my' => (clone $active)->where('assignee_id', $user->id)->count(),
            'personal' => (clone $active)->where('creator_id', $user->id)->where('assignee_id', $user->id)->where('type', Task::TYPE_PERSONAL)->count(),
            'from_manager' => (clone $active)->where('assignee_id', $user->id)->whereNotNull('assigned_by_id')->where('assigned_by_id', '!=', $user->id)->count(),
            'assigned_by_me' => (clone $active)->where('assigned_by_id', $user->id)->count(),
            'participating' => (clone $active)->whereHas('participants', fn (Builder $query): Builder => $query->where('user_id', $user->id))->count(),
            'boards' => (clone $active)->where('board_id', $this->boardId)->count(),
            'archive' => $this->visibleTaskQuery($user)->archived()->count(),
        ];
    }

    /**
     * @return list<array{label: string, value: int, tone: string, icon: string}>
     */
    private function kpis(User $user): array
    {
        $active = $this->visibleTaskQuery($user)->active();

        return [
            ['label' => 'Назначено мне', 'value' => (clone $active)->where('assignee_id', $user->id)->count(), 'tone' => 'blue', 'icon' => 'check-square'],
            ['label' => 'В работе', 'value' => (clone $active)->where('status', Task::STATUS_IN_PROGRESS)->count(), 'tone' => 'green', 'icon' => 'clock'],
            ['label' => 'На HOLD', 'value' => (clone $active)->where('status', Task::STATUS_ON_HOLD)->count(), 'tone' => 'amber', 'icon' => 'alert'],
            ['label' => 'Просрочено', 'value' => (clone $active)->whereNotNull('due_at')->where('due_at', '<', now())->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_ARCHIVED])->count(), 'tone' => 'red', 'icon' => 'calendar'],
        ];
    }

    /**
     * @param callable(Task, User): mixed $action
     */
    private function runTaskAction(int $taskId, callable $action, string $successTitle): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $task = $this->visibleTaskQuery($user)->findOrFail($taskId);
            $action($task, $user);
            $this->selectedTaskId = $taskId;
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Действие не выполнено')->body($exception->getMessage())->danger()->send();
        }
    }
}
