<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class TaskAccessService
{
    public function canAccessTasks(User $user): bool
    {
        return $user->exists;
    }

    public function canCreate(User $user): bool
    {
        return $user->exists;
    }

    public function canManageBoards(User $user): bool
    {
        return $this->isPrivileged($user) || $this->userCan($user, 'tasks.manage_boards');
    }

    public function isPrivileged(User $user): bool
    {
        return $this->userHasRole($user, 'super_admin') || $this->userCan($user, 'tasks.view_all');
    }

    public function canView(Task $task, User $user): bool
    {
        if ($this->isPrivileged($user)) {
            return true;
        }

        if ($this->isDirectTaskMember($task, $user)) {
            return true;
        }

        if ($this->isManagerOfTaskMember($task, $user)) {
            return true;
        }

        if (
            $this->userCan($user, 'tasks.view_department')
            && $user->department_id !== null
            && (int) $task->department_id === (int) $user->department_id
            && ! $this->isPrivatePersonalTask($task)
        ) {
            return true;
        }

        return false;
    }

    public function scopeVisibleTasks(Builder $query, User $user): Builder
    {
        if ($this->isPrivileged($user)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('creator_id', $user->id)
                ->orWhere('assignee_id', $user->id)
                ->orWhere('assigned_by_id', $user->id)
                ->orWhereHas('participants', fn (Builder $query): Builder => $query->where('user_id', $user->id))
                ->orWhereHas('assignee', fn (Builder $query): Builder => $query->where('manager_id', $user->id));

            if ($this->userCan($user, 'tasks.view_department') && $user->department_id !== null) {
                $query->orWhere(function (Builder $query) use ($user): void {
                    $query
                        ->where('department_id', $user->department_id)
                        ->where('type', '!=', Task::TYPE_PERSONAL);
                });
            }
        });
    }

    public function canAssignTo(User $actor, User $assignee): bool
    {
        if ((int) $actor->id === (int) $assignee->id) {
            return true;
        }

        if ($this->isPrivileged($actor) || $this->userCan($actor, 'tasks.assign')) {
            return true;
        }

        return (int) $assignee->manager_id === (int) $actor->id;
    }

    public function canUpdate(Task $task, User $user): bool
    {
        return $this->canView($task, $user)
            && (
                $this->isPrivileged($user)
                || $this->userCan($user, 'tasks.update')
                || (int) $task->creator_id === (int) $user->id
                || (int) $task->assignee_id === (int) $user->id
                || (int) $task->assigned_by_id === (int) $user->id
            );
    }

    public function canComment(Task $task, User $user): bool
    {
        return $this->canView($task, $user)
            && ($this->userCan($user, 'tasks.comment') || $this->isDirectTaskMember($task, $user) || $this->isManagerOfTaskMember($task, $user));
    }

    public function canHold(Task $task, User $user): bool
    {
        return $this->canUpdate($task, $user)
            && ($this->userCan($user, 'tasks.hold') || $this->isDirectTaskMember($task, $user));
    }

    public function canResume(Task $task, User $user): bool
    {
        return $this->canUpdate($task, $user)
            && ($this->userCan($user, 'tasks.resume') || $this->isDirectTaskMember($task, $user));
    }

    public function canComplete(Task $task, User $user): bool
    {
        return $this->canUpdate($task, $user)
            && ($this->userCan($user, 'tasks.complete') || $this->isDirectTaskMember($task, $user));
    }

    public function canArchive(Task $task, User $user): bool
    {
        return $this->canUpdate($task, $user)
            && ($this->userCan($user, 'tasks.archive') || (int) $task->creator_id === (int) $user->id);
    }

    public function canViewBoard(TaskBoard $board, User $user): bool
    {
        if ($this->isPrivileged($user)) {
            return true;
        }

        if ((int) $board->owner_id === (int) $user->id || (int) $board->created_by === (int) $user->id) {
            return true;
        }

        if ($board->visibility === TaskBoard::VISIBILITY_COMPANY) {
            return true;
        }

        if (
            $board->visibility === TaskBoard::VISIBILITY_DEPARTMENT
            && $user->department_id !== null
            && (int) $board->department_id === (int) $user->department_id
        ) {
            return $this->userCan($user, 'tasks.view_department');
        }

        return false;
    }

    /**
     * @return Collection<int, User>
     */
    public function possibleAssignees(User $actor): Collection
    {
        if ($this->isPrivileged($actor) || $this->userCan($actor, 'tasks.assign')) {
            return User::query()
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get();
        }

        return User::query()
            ->whereNull('archived_at')
            ->where(function (Builder $query) use ($actor): void {
                $query
                    ->whereKey($actor->id)
                    ->orWhere('manager_id', $actor->id);
            })
            ->orderBy('name')
            ->get();
    }

    private function isDirectTaskMember(Task $task, User $user): bool
    {
        if ((int) $task->creator_id === (int) $user->id) {
            return true;
        }

        if ((int) $task->assignee_id === (int) $user->id) {
            return true;
        }

        if ((int) $task->assigned_by_id === (int) $user->id) {
            return true;
        }

        if ($task->relationLoaded('participants')) {
            return $task->participants->contains('user_id', $user->id);
        }

        return $task->exists && $task->participants()->where('user_id', $user->id)->exists();
    }

    private function isManagerOfTaskMember(Task $task, User $user): bool
    {
        $assignee = $task->relationLoaded('assignee') ? $task->assignee : null;
        $creator = $task->relationLoaded('creator') ? $task->creator : null;

        return ((int) $assignee?->manager_id === (int) $user->id)
            || ((int) $creator?->manager_id === (int) $user->id);
    }

    private function isPrivatePersonalTask(Task $task): bool
    {
        if ($task->type !== Task::TYPE_PERSONAL) {
            return false;
        }

        $board = $task->relationLoaded('board') ? $task->board : null;

        return $board === null || $board->visibility === TaskBoard::VISIBILITY_PRIVATE;
    }

    private function userHasRole(User $user, string $role): bool
    {
        try {
            return $user->hasRole($role);
        } catch (Throwable) {
            return false;
        }
    }

    private function userCan(User $user, string $permission): bool
    {
        try {
            return $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }
}
