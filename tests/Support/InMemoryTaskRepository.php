<?php

namespace Tests\Support;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

/**
 * Repository giả lưu trong bộ nhớ, giúp test tầng Domain không cần database.
 */
final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<int, Task> */
    private array $tasks = [];

    private int $nextId = 1;

    public function allForUser(int $userId): array
    {
        return array_values(array_map(
            fn (Task $task) => clone $task,
            array_filter(array_reverse($this->tasks), fn (Task $task) => $task->belongsTo($userId)),
        ));
    }

    public function findForUser(int $id, int $userId): ?Task
    {
        $task = $this->tasks[$id] ?? null;

        return $task?->belongsTo($userId) ? clone $task : null;
    }

    public function save(Task $task): Task
    {
        $id = $task->id() ?? $this->nextId++;
        $saved = Task::restore(
            $id,
            $task->userId(),
            $task->title(),
            $task->isCompleted(),
            $task->imagePath(),
            $task->priority(),
            $task->category(),
            $task->dueDate(),
            $task->createdAt(),
        );

        return clone ($this->tasks[$id] = $saved);
    }

    public function delete(int $id): void
    {
        unset($this->tasks[$id]);
    }
}
