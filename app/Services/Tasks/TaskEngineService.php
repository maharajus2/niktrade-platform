<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskColumn;
use App\Models\TaskComment;
use App\Models\TaskHold;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaskEngineService
{
    public function __construct(
        private readonly TaskAccessService $access,
        private readonly TaskBoardService $boards,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function createTask(User $actor, array $data): Task
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }

        $assignee = User::query()->findOrFail((int) ($data['assignee_id'] ?? $actor->id));

        if (! $this->access->canAssignTo($actor, $assignee)) {
            throw new AuthorizationException('You cannot assign tasks to this employee.');
        }

        return DB::transaction(function () use ($actor, $assignee, $data, $title): Task {
            $board = isset($data['board_id'])
                ? $this->boardForTask((int) $data['board_id'], $actor, $assignee)
                : $this->boards->defaultBoardFor($assignee);

            $column = $board->columns()->orderBy('sort_order')->first();

            if (! $column instanceof TaskColumn) {
                $this->boards->ensureDefaultColumns($board);
                $column = $board->columns()->orderBy('sort_order')->firstOrFail();
            }

            $task = Task::query()->create([
                'board_id' => $board->id,
                'column_id' => $column->id,
                'title' => $title,
                'description' => blank($data['description'] ?? null) ? null : trim((string) $data['description']),
                'type' => $data['type'] ?? ((int) $assignee->id === (int) $actor->id ? Task::TYPE_PERSONAL : Task::TYPE_MANAGER_ASSIGNED),
                'status' => $this->statusForColumn($column),
                'priority' => $data['priority'] ?? Task::PRIORITY_NORMAL,
                'creator_id' => $actor->id,
                'assignee_id' => $assignee->id,
                'assigned_by_id' => (int) $assignee->id === (int) $actor->id ? null : $actor->id,
                'department_id' => $data['department_id'] ?? $assignee->department_id ?? $actor->department_id,
                'parent_id' => $data['parent_id'] ?? null,
                'planned_start_at' => $this->parseDateTime($data['planned_start_at'] ?? null),
                'due_at' => $this->parseDateTime($data['due_at'] ?? null),
                'sla_minutes' => blank($data['sla_minutes'] ?? null) ? null : (int) $data['sla_minutes'],
                'sla_started_at' => filled($data['sla_minutes'] ?? null) ? now() : null,
                'sla_due_at' => filled($data['sla_minutes'] ?? null) ? now()->addMinutes((int) $data['sla_minutes']) : null,
            ]);

            $this->attachParticipants($task, $actor, $data['participant_ids'] ?? []);
            $this->log($task, $actor, 'created', new: $task->only(['title', 'type', 'status', 'priority', 'assignee_id']));

            if ((int) $assignee->id !== (int) $actor->id) {
                $this->systemComment($task, $actor, 'Задача назначена сотруднику.');
            }

            return $task->load(['board.columns', 'column', 'creator', 'assignee', 'assignedBy', 'participants.user', 'comments.user']);
        });
    }

    public function moveToColumn(Task $task, TaskColumn $column, User $actor): Task
    {
        if (! $this->access->canUpdate($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot update this task.');
        }

        if ((int) $task->board_id !== (int) $column->board_id) {
            throw new InvalidArgumentException('Column belongs to another board.');
        }

        $old = $task->only(['column_id', 'status', 'completed_at']);
        $status = $this->statusForColumn($column);

        $task->forceFill([
            'column_id' => $column->id,
            'status' => $status,
            'completed_at' => $column->is_final ? now() : null,
            'completed_by' => $column->is_final ? $actor->id : null,
        ])->save();

        $this->log($task, $actor, 'moved', $old, $task->only(['column_id', 'status', 'completed_at']));

        return $task->refresh()->load(['column', 'board.columns']);
    }

    public function addComment(Task $task, User $actor, string $body): TaskComment
    {
        if (! $this->access->canComment($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot comment this task.');
        }

        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('Comment body is required.');
        }

        $comment = $task->comments()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'is_system' => false,
        ]);

        $this->log($task, $actor, 'commented', new: ['comment_id' => $comment->id]);

        return $comment;
    }

    public function putOnHold(Task $task, User $actor, string $reason): Task
    {
        if (! $this->access->canHold($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot put this task on hold.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Hold reason is required.');
        }

        return DB::transaction(function () use ($task, $actor, $reason): Task {
            $old = $task->only(['status', 'hold_started_at', 'hold_reason', 'hold_by']);

            $task->forceFill([
                'status' => Task::STATUS_ON_HOLD,
                'hold_started_at' => now(),
                'hold_reason' => $reason,
                'hold_by' => $actor->id,
            ])->save();

            TaskHold::query()->create([
                'task_id' => $task->id,
                'started_by' => $actor->id,
                'started_at' => now(),
                'reason' => $reason,
            ]);

            $this->systemComment($task, $actor, 'Задача поставлена на HOLD: '.$reason);
            $this->log($task, $actor, 'hold_started', $old, $task->only(['status', 'hold_started_at', 'hold_reason', 'hold_by']));

            return $task->refresh();
        });
    }

    public function resume(Task $task, User $actor, ?string $comment = null): Task
    {
        if (! $this->access->canResume($task->loadMissing(['assignee', 'creator', 'participants', 'board', 'column']), $actor)) {
            throw new AuthorizationException('You cannot resume this task.');
        }

        return DB::transaction(function () use ($task, $actor, $comment): Task {
            $old = $task->only(['status', 'hold_started_at', 'hold_reason', 'hold_by']);
            $status = $task->column instanceof TaskColumn
                ? $this->statusForColumn($task->column)
                : Task::STATUS_OPEN;

            $task->forceFill([
                'status' => $status === Task::STATUS_COMPLETED ? Task::STATUS_IN_PROGRESS : $status,
                'hold_started_at' => null,
                'hold_reason' => null,
                'hold_by' => null,
            ])->save();

            $task->holds()
                ->whereNull('ended_at')
                ->update([
                    'ended_by' => $actor->id,
                    'ended_at' => now(),
                    'resume_comment' => blank($comment) ? null : trim((string) $comment),
                    'updated_at' => now(),
                ]);

            $this->systemComment($task, $actor, filled($comment) ? 'HOLD снят: '.trim((string) $comment) : 'HOLD снят.');
            $this->log($task, $actor, 'resumed', $old, $task->only(['status', 'hold_started_at', 'hold_reason', 'hold_by']));

            return $task->refresh();
        });
    }

    public function complete(Task $task, User $actor): Task
    {
        if (! $this->access->canComplete($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot complete this task.');
        }

        $old = $task->only(['status', 'completed_at', 'completed_by']);
        $finalColumn = $task->board?->columns()->where('is_final', true)->orderBy('sort_order')->first();

        $task->forceFill([
            'column_id' => $finalColumn?->id ?? $task->column_id,
            'status' => Task::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $actor->id,
        ])->save();

        $this->log($task, $actor, 'completed', $old, $task->only(['column_id', 'status', 'completed_at', 'completed_by']));

        return $task->refresh();
    }

    public function archive(Task $task, User $actor): Task
    {
        if (! $this->access->canArchive($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot archive this task.');
        }

        $old = $task->only(['status', 'archived_at', 'archived_by']);

        $task->forceFill([
            'status' => Task::STATUS_ARCHIVED,
            'archived_at' => now(),
            'archived_by' => $actor->id,
        ])->save();

        $this->log($task, $actor, 'archived', $old, $task->only(['status', 'archived_at', 'archived_by']));

        return $task->refresh();
    }

    public function addParticipant(Task $task, User $actor, User $participant, string $role = TaskParticipant::ROLE_PARTICIPANT): TaskParticipant
    {
        if (! $this->access->canUpdate($task->loadMissing(['assignee', 'creator', 'participants', 'board']), $actor)) {
            throw new AuthorizationException('You cannot update participants.');
        }

        $participantRow = $task->participants()->updateOrCreate(
            ['user_id' => $participant->id],
            ['role' => $role, 'added_by' => $actor->id],
        );

        $this->log($task, $actor, 'participant_added', new: ['user_id' => $participant->id, 'role' => $role]);

        return $participantRow;
    }

    private function boardForTask(int $boardId, User $actor, User $assignee)
    {
        $board = \App\Models\TaskBoard::query()->with('columns')->findOrFail($boardId);

        if (
            ! $this->access->canViewBoard($board, $actor)
            && ! ((int) $board->owner_id === (int) $assignee->id && $this->access->canAssignTo($actor, $assignee))
        ) {
            throw new AuthorizationException('You cannot use this task board.');
        }

        return $board;
    }

    /**
     * @param mixed $value
     */
    private function parseDateTime($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    private function statusForColumn(TaskColumn $column): string
    {
        if ($column->is_hold) {
            return Task::STATUS_ON_HOLD;
        }

        return match ($column->slug) {
            TaskColumn::SLUG_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
            TaskColumn::SLUG_REVIEW => Task::STATUS_REVIEW,
            TaskColumn::SLUG_DONE => Task::STATUS_COMPLETED,
            default => Task::STATUS_OPEN,
        };
    }

    /**
     * @param mixed $participantIds
     */
    private function attachParticipants(Task $task, User $actor, $participantIds): void
    {
        collect((array) $participantIds)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => $id === (int) $task->creator_id || $id === (int) $task->assignee_id)
            ->each(function (int $id) use ($task, $actor): void {
                $task->participants()->updateOrCreate(
                    ['user_id' => $id],
                    [
                        'role' => TaskParticipant::ROLE_PARTICIPANT,
                        'added_by' => $actor->id,
                    ],
                );
            });
    }

    /**
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     */
    private function log(Task $task, User $actor, string $action, ?array $old = null, ?array $new = null, ?string $comment = null): void
    {
        TaskActivityLog::query()->create([
            'task_id' => $task->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    private function systemComment(Task $task, User $actor, string $body): void
    {
        $task->comments()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'is_system' => true,
        ]);
    }
}
