<?php

namespace App\Services\Approvals;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function start(Model $approvable, User $actor, ?User $initialApprover = null): ApprovalWorkflow
    {
        return DB::transaction(function () use ($approvable, $actor, $initialApprover): ApprovalWorkflow {
            $workflow = $approvable->approvalWorkflow()->firstOrNew();
            $workflow->fill([
                'status' => ApprovalWorkflow::STATUS_PENDING,
                'submitted_by' => $actor->getKey(),
                'current_approver_id' => $initialApprover?->getKey(),
            ]);
            $workflow->save();

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_PENDING, [
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

            $this->recordEvent(
                workflow: $workflow,
                actor: $actor,
                action: ApprovalWorkflowEvent::ACTION_CREATED,
                fromStatus: null,
                toStatus: ApprovalWorkflow::STATUS_PENDING,
                comment: 'Заявка создана.',
                forwardedTo: $initialApprover,
            );

            return $workflow->refresh();
        });
    }

    public function approveFinally(Model $approvable, User $actor, string $comment): int
    {
        $this->ensureComment($comment);

        return DB::transaction(function () use ($approvable, $actor, $comment): int {
            $workflow = $this->workflow($approvable);
            $fromStatus = $workflow->status;

            $created = 0;

            if (method_exists($approvable, 'handleApprovalWorkflowApproved')) {
                $created = (int) $approvable->handleApprovalWorkflowApproved($actor, $comment);
            }

            $workflow->update([
                'status' => ApprovalWorkflow::STATUS_APPROVED,
                'current_approver_id' => null,
                'completed_at' => now(),
            ]);

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_APPROVED, [
                'manager_comment' => $comment,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->recordEvent(
                workflow: $workflow,
                actor: $actor,
                action: ApprovalWorkflowEvent::ACTION_APPROVED,
                fromStatus: $fromStatus,
                toStatus: ApprovalWorkflow::STATUS_APPROVED,
                comment: $comment,
                metadata: ['created_schedule_entries_count' => $created],
            );

            if ($created > 0) {
                $this->recordEvent(
                    workflow: $workflow,
                    actor: null,
                    action: ApprovalWorkflowEvent::ACTION_SYSTEM,
                    fromStatus: ApprovalWorkflow::STATUS_APPROVED,
                    toStatus: ApprovalWorkflow::STATUS_APPROVED,
                    comment: "Календарь сотрудника обновлён автоматически. Создано событий: {$created}.",
                );
            }

            return $created;
        });
    }

    public function reject(Model $approvable, User $actor, string $comment): void
    {
        $this->ensureComment($comment);

        DB::transaction(function () use ($approvable, $actor, $comment): void {
            $workflow = $this->workflow($approvable);
            $fromStatus = $workflow->status;

            $workflow->update([
                'status' => ApprovalWorkflow::STATUS_REJECTED,
                'current_approver_id' => null,
                'completed_at' => now(),
            ]);

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_REJECTED, [
                'manager_comment' => $comment,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->recordEvent($workflow, $actor, ApprovalWorkflowEvent::ACTION_REJECTED, $fromStatus, ApprovalWorkflow::STATUS_REJECTED, $comment);
        });
    }

    public function returnToRequester(Model $approvable, User $actor, string $comment): void
    {
        $this->ensureComment($comment);

        DB::transaction(function () use ($approvable, $actor, $comment): void {
            $workflow = $this->workflow($approvable);
            $fromStatus = $workflow->status;

            $workflow->update([
                'status' => ApprovalWorkflow::STATUS_RETURNED,
                'current_approver_id' => null,
                'completed_at' => null,
            ]);

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_RETURNED, [
                'manager_comment' => $comment,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->recordEvent($workflow, $actor, ApprovalWorkflowEvent::ACTION_RETURNED, $fromStatus, ApprovalWorkflow::STATUS_RETURNED, $comment);
        });
    }

    public function forward(Model $approvable, User $actor, User $nextApprover, string $comment): void
    {
        $this->ensureComment($comment);

        DB::transaction(function () use ($approvable, $actor, $nextApprover, $comment): void {
            $workflow = $this->workflow($approvable);
            $fromStatus = $workflow->status;

            $workflow->update([
                'status' => ApprovalWorkflow::STATUS_FORWARDED,
                'current_approver_id' => $nextApprover->getKey(),
                'completed_at' => null,
            ]);

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_FORWARDED, [
                'manager_comment' => $comment,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->recordEvent($workflow, $actor, ApprovalWorkflowEvent::ACTION_FORWARDED, $fromStatus, ApprovalWorkflow::STATUS_FORWARDED, $comment, $nextApprover);
        });
    }

    public function cancel(Model $approvable, User $actor, ?string $comment = null): void
    {
        DB::transaction(function () use ($approvable, $actor, $comment): void {
            $workflow = $approvable->approvalWorkflow()->first();
            $fromStatus = $workflow?->status;

            if ($workflow) {
                $workflow->update([
                    'status' => ApprovalWorkflow::STATUS_CANCELLED,
                    'current_approver_id' => null,
                    'completed_at' => now(),
                ]);

                $this->recordEvent($workflow, $actor, ApprovalWorkflowEvent::ACTION_CANCELLED, $fromStatus, ApprovalWorkflow::STATUS_CANCELLED, $comment);
            }

            $this->syncApprovableStatus($approvable, ApprovalWorkflow::STATUS_CANCELLED, [
                'manager_comment' => $comment,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);
        });
    }

    public function archive(Model $approvable, User $actor): void
    {
        DB::transaction(function () use ($approvable, $actor): void {
            $approvable->forceFill([
                'archived_at' => now(),
                'archived_by' => $actor->getKey(),
            ])->save();

            $workflow = $this->workflowForLifecycle($approvable, $actor);

            $this->recordEvent(
                workflow: $workflow,
                actor: $actor,
                action: ApprovalWorkflowEvent::ACTION_ARCHIVED,
                fromStatus: $workflow->status,
                toStatus: $workflow->status,
                comment: 'Заявка перемещена в архив.',
            );
        });
    }

    public function moveToDeleted(Model $approvable, User $actor): void
    {
        DB::transaction(function () use ($approvable, $actor): void {
            $approvable->forceFill([
                'deleted_at' => now(),
                'deleted_by' => $actor->getKey(),
            ])->save();

            $workflow = $this->workflowForLifecycle($approvable, $actor);

            $this->recordEvent(
                workflow: $workflow,
                actor: $actor,
                action: ApprovalWorkflowEvent::ACTION_MOVED_TO_DELETED,
                fromStatus: $workflow->status,
                toStatus: $workflow->status,
                comment: 'Заявка перемещена в удалённые.',
            );
        });
    }

    public function restore(Model $approvable, User $actor): void
    {
        DB::transaction(function () use ($approvable, $actor): void {
            $approvable->forceFill([
                'deleted_at' => null,
                'deleted_by' => null,
            ])->save();

            $workflow = $this->workflowForLifecycle($approvable, $actor);

            $this->recordEvent(
                workflow: $workflow,
                actor: $actor,
                action: ApprovalWorkflowEvent::ACTION_RESTORED,
                fromStatus: $workflow->status,
                toStatus: $workflow->status,
                comment: 'Заявка восстановлена из удалённых.',
            );
        });
    }

    private function workflow(Model $approvable): ApprovalWorkflow
    {
        /** @var ApprovalWorkflow|null $workflow */
        $workflow = $approvable->approvalWorkflow()->first();

        if (! $workflow) {
            throw ValidationException::withMessages([
                'approval' => 'Маршрут согласования не найден.',
            ]);
        }

        if ($workflow->isTerminal()) {
            throw ValidationException::withMessages([
                'approval' => 'Заявка уже завершена.',
            ]);
        }

        return $workflow;
    }

    private function workflowForLifecycle(Model $approvable, User $actor): ApprovalWorkflow
    {
        /** @var ApprovalWorkflow $workflow */
        $workflow = $approvable->approvalWorkflow()->firstOrCreate([], [
            'status' => (string) ($approvable->getAttribute('status') ?? ApprovalWorkflow::STATUS_PENDING),
            'submitted_by' => $approvable->getAttribute('requested_by') ?: $actor->getKey(),
            'current_approver_id' => null,
        ]);

        return $workflow;
    }

    private function syncApprovableStatus(Model $approvable, string $status, array $extra = []): void
    {
        $updates = ['status' => $status] + $extra;

        if ($status === ApprovalWorkflow::STATUS_APPROVED && array_key_exists('created_schedule_entries_count', $extra)) {
            $updates['created_schedule_entries_count'] = $extra['created_schedule_entries_count'];
        }

        $approvable->forceFill($updates)->save();
    }

    private function recordEvent(
        ApprovalWorkflow $workflow,
        ?User $actor,
        string $action,
        ?string $fromStatus,
        string $toStatus,
        ?string $comment,
        ?User $forwardedTo = null,
        array $metadata = [],
    ): void {
        $workflow->events()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'forwarded_to_id' => $forwardedTo?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $this->afterTransition($workflow, $action);
    }

    protected function afterTransition(ApprovalWorkflow $workflow, string $action): void
    {
        // Future hook for Telegram, chat, email, and dashboard notifications.
    }

    private function ensureComment(string $comment): void
    {
        if (blank($comment)) {
            throw ValidationException::withMessages([
                'comment' => 'Укажите комментарий.',
            ]);
        }
    }
}
