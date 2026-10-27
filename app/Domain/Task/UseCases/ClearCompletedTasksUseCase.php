<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Repositories\TaskRepositoryInterface;

/**
 * Xóa tất cả công việc đã hoàn thành của người dùng; trả về số việc đã xóa.
 */
final class ClearCompletedTasksUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private DeleteTaskUseCase $deleteTask,
    ) {
    }

    public function execute(int $userId): int
    {
        $completed = array_filter($this->tasks->allForUser($userId), fn ($task) => $task->isCompleted());

        foreach ($completed as $task) {
            $this->deleteTask->remove($task);
        }

        return count($completed);
    }
}
