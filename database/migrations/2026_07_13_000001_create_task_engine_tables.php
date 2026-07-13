<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_boards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('visibility')->default('private');
            $table->string('type')->default('personal');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'is_default']);
            $table->index(['department_id', 'visibility']);
            $table->index(['type', 'is_archived']);
        });

        Schema::create('task_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained('task_boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->boolean('is_hold')->default(false);
            $table->timestamps();

            $table->unique(['board_id', 'slug']);
            $table->index(['board_id', 'sort_order']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->nullable()->constrained('task_boards')->nullOnDelete();
            $table->foreignId('column_id')->nullable()->constrained('task_columns')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('personal');
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('sla_minutes')->nullable();
            $table->timestamp('sla_started_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hold_started_at')->nullable();
            $table->text('hold_reason')->nullable();
            $table->foreignId('hold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assignee_id', 'status', 'due_at']);
            $table->index(['creator_id', 'status']);
            $table->index(['assigned_by_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['board_id', 'column_id']);
            $table->index(['archived_at', 'status']);
        });

        Schema::create('task_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('participant');
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });

        Schema::create('task_holds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->text('reason');
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->text('resume_comment')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'ended_at']);
        });

        Schema::create('task_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at');

            $table->index(['task_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activity_logs');
        Schema::dropIfExists('task_holds');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_participants');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_columns');
        Schema::dropIfExists('task_boards');
    }
};
