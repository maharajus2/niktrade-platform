<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\User;

class TaskNotificationService
{
    public function taskAssigned(Task $task, User $actor): void
    {
        // Phase 1 placeholder: notifications will be routed through the future internal inbox.
    }

    public function taskCommented(Task $task, User $actor): void
    {
        // Phase 1 placeholder.
    }

    public function taskStatusChanged(Task $task, User $actor): void
    {
        // Phase 1 placeholder.
    }
}
