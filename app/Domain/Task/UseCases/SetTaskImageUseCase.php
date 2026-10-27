<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;

/**
 * Thêm/đổi ảnh của công việc, hoặc gỡ ảnh khi $image là null.
 */
final class SetTaskImageUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private ImageStorageInterface $images,
        private TaskActivityRepositoryInterface $activities,
    ) {
    }

    public function execute(int $userId, int $id, ?ImageUpload $image): Task
    {
        $task = $this->tasks->findForUser($id, $userId) ?? throw TaskNotFoundException::withId($id);

        if ($image === null && ! $task->hasImage()) {
            return $task;
        }

        $previous = $task->replaceImage($image ? $this->images->store($image, Task::IMAGE_FOLDER) : null);
        $task = $this->tasks->save($task);

        if ($previous !== null) {
            $this->images->delete($previous);
        }

        $this->activities->add(TaskActivity::record($task, $image ? TaskAction::ImageUpdated : TaskAction::ImageRemoved));

        return $task;
    }
}
