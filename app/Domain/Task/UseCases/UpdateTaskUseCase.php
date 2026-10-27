<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use App\Domain\Task\ValueObjects\TaskDetails;

/**
 * Sửa tên, danh mục, mức ưu tiên, hạn chót của công việc.
 */
final class UpdateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private TaskActivityRepositoryInterface $activities,
    ) {
    }

    public function execute(int $userId, int $id, TaskDetails $details): Task
    {
        $task = $this->tasks->findForUser($id, $userId) ?? throw TaskNotFoundException::withId($id);

        if (! $task->updateDetails($details)) {
            return $task;
        }

        $task = $this->tasks->save($task);
        $this->activities->add(TaskActivity::record($task, TaskAction::Updated));

        return $task;
    }
}
