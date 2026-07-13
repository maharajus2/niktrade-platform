<?php

namespace App\Services\Tasks;

use App\Models\TaskBoard;
use App\Models\TaskColumn;
use App\Models\User;

class TaskBoardService
{
    /**
     * @return list<array{name: string, slug: string, color: string, sort_order: int, is_final?: bool}>
     */
    public static function defaultColumns(): array
    {
        return [
            ['name' => 'Новые', 'slug' => TaskColumn::SLUG_NEW, 'color' => '#2f80ed', 'sort_order' => 10],
            ['name' => 'В работе', 'slug' => TaskColumn::SLUG_IN_PROGRESS, 'color' => '#22c55e', 'sort_order' => 20],
            ['name' => 'На проверке', 'slug' => TaskColumn::SLUG_REVIEW, 'color' => '#8b5cf6', 'sort_order' => 30],
            ['name' => 'Готово', 'slug' => TaskColumn::SLUG_DONE, 'color' => '#64748b', 'sort_order' => 40, 'is_final' => true],
        ];
    }

    public function defaultBoardFor(User $user): TaskBoard
    {
        $board = TaskBoard::query()->firstOrCreate(
            [
                'owner_id' => $user->id,
                'type' => TaskBoard::TYPE_PERSONAL,
                'is_default' => true,
            ],
            [
                'name' => 'Моя доска',
                'visibility' => TaskBoard::VISIBILITY_PRIVATE,
                'department_id' => $user->department_id,
                'created_by' => $user->id,
            ],
        );

        $this->ensureDefaultColumns($board);

        return $board->load('columns');
    }

    public function createPersonalBoard(User $owner, User $creator, array $data): TaskBoard
    {
        $board = TaskBoard::query()->create([
            'name' => trim((string) $data['name']),
            'description' => blank($data['description'] ?? null) ? null : trim((string) $data['description']),
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'visibility' => $data['visibility'] ?? TaskBoard::VISIBILITY_PRIVATE,
            'type' => TaskBoard::TYPE_PERSONAL,
            'is_default' => false,
            'created_by' => $creator->id,
        ]);

        $this->ensureDefaultColumns($board);

        return $board->load('columns');
    }

    public function ensureDefaultColumns(TaskBoard $board): void
    {
        if ($board->columns()->exists()) {
            return;
        }

        foreach (self::defaultColumns() as $column) {
            $board->columns()->create([
                'name' => $column['name'],
                'slug' => $column['slug'],
                'color' => $column['color'],
                'sort_order' => $column['sort_order'],
                'is_final' => $column['is_final'] ?? false,
                'is_hold' => false,
            ]);
        }
    }
}
