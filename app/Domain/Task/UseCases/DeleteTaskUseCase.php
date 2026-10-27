<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;

final class DeleteTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private ImageStorageInterface $images,
        private TaskActivityRepositoryInterface $activities,
    ) {
    }

    public function execute(int $userId, int $id): void
    {
        $task = $this->tasks->findForUser($id, $userId) ?? throw TaskNotFoundException::withId($id);

        $this->remove($task);
    }

    /**
     * Xóa công việc, file ảnh của nó và ghi lịch sử. Dùng chung cho xóa hàng loạt.
     */
    public function remove(Task $task): void
    {
        $this->tasks->delete($task->id());

        if ($task->hasImage()) {
            $this->images->delete($task->imagePath());
        }

        $this->activities->add(TaskActivity::record($task, TaskAction::Deleted));
    }
}
