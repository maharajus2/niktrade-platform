<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskParticipant;
use App\Models\User;
use App\Services\Tasks\TaskAccessService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class TaskAccessServiceTest extends TestCase
{
    public function test_manager_can_assign_only_direct_report(): void
    {
        $service = new TaskAccessService();
        $manager = $this->user(1);
        $directReport = $this->user(2, managerId: 1);
        $otherEmployee = $this->user(3, managerId: 99);

        $this->assertTrue($service->canAssignTo($manager, $directReport));
        $this->assertFalse($service->canAssignTo($manager, $otherEmployee));
    }

    public function test_manager_can_view_direct_report_task(): void
    {
        $service = new TaskAccessService();
        $manager = $this->user(1);
        $directReport = $this->user(2, managerId: 1);

        $task = new Task([
            'type' => Task::TYPE_MANAGER_ASSIGNED,
            'creator_id' => 4,
            'assignee_id' => 2,
            'status' => Task::STATUS_OPEN,
        ]);
        $task->setRelation('assignee', $directReport);
        $task->setRelation('creator', $this->user(4));
        $task->setRelation('participants', new EloquentCollection());
        $task->setRelation('board', $this->privateBoard(2));

        $this->assertTrue($service->canView($task, $manager));
    }

    public function test_participant_can_view_and_comment(): void
    {
        $service = new TaskAccessService();
        $participant = $this->user(5);

        $task = new Task([
            'type' => Task::TYPE_PERSONAL,
            'creator_id' => 1,
            'assignee_id' => 2,
            'status' => Task::STATUS_OPEN,
        ]);
        $task->setRelation('assignee', $this->user(2));
        $task->setRelation('creator', $this->user(1));
        $task->setRelation('board', $this->privateBoard(2));
        $task->setRelation('participants', new EloquentCollection([
            new TaskParticipant(['user_id' => 5]),
        ]));

        $this->assertTrue($service->canView($task, $participant));
        $this->assertTrue($service->canComment($task, $participant));
    }

    public function test_private_personal_task_is_not_visible_to_unrelated_employee(): void
    {
        $service = new TaskAccessService();

        $task = new Task([
            'type' => Task::TYPE_PERSONAL,
            'creator_id' => 1,
            'assignee_id' => 2,
            'department_id' => 7,
            'status' => Task::STATUS_OPEN,
        ]);
        $task->setRelation('assignee', $this->user(2));
        $task->setRelation('creator', $this->user(1));
        $task->setRelation('board', $this->privateBoard(2));
        $task->setRelation('participants', new EloquentCollection());

        $this->assertFalse($service->canView($task, $this->user(9, departmentId: 7)));
    }

    private function user(int $id, ?int $managerId = null, ?int $departmentId = null): User
    {
        $user = new User([
            'name' => "User {$id}",
            'email' => "user{$id}@example.test",
            'manager_id' => $managerId,
            'department_id' => $departmentId,
        ]);
        $user->id = $id;
        $user->exists = true;
        $user->setRelation('roles', new EloquentCollection());
        $user->setRelation('permissions', new EloquentCollection());

        return $user;
    }

    private function privateBoard(int $ownerId): TaskBoard
    {
        $board = new TaskBoard([
            'owner_id' => $ownerId,
            'visibility' => TaskBoard::VISIBILITY_PRIVATE,
            'type' => TaskBoard::TYPE_PERSONAL,
        ]);
        $board->id = 1;
        $board->exists = true;

        return $board;
    }
}
