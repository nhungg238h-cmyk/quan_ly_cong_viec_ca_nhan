<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;

/**
 * Đánh dấu công việc là hoàn thành hoặc chưa hoàn thành.
 */
final class SetTaskCompletionUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private TaskActivityRepositoryInterface $activities,
    ) {
    }

    public function execute(int $userId, int $id, bool $completed): Task
    {
        $task = $this->tasks->findForUser($id, $userId) ?? throw TaskNotFoundException::withId($id);

        if ($task->isCompleted() === $completed) {
            return $task;
        }

        $completed ? $task->markCompleted() : $task->markIncomplete();
        $task = $this->tasks->save($task);

        $this->activities->add(TaskActivity::record($task, $completed ? TaskAction::Completed : TaskAction::Reopened));

        return $task;
    }
}
