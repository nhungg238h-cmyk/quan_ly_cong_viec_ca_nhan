<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use App\Domain\Task\ValueObjects\TaskDetails;

final class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private ImageStorageInterface $images,
        private TaskActivityRepositoryInterface $activities,
    ) {
    }

    public function execute(int $userId, TaskDetails $details, ?ImageUpload $image = null): Task
    {
        // Kiểm tra dữ liệu trước để không lưu ảnh thừa khi dữ liệu không hợp lệ.
        $task = Task::create($userId, $details);

        if ($image !== null) {
            $task->replaceImage($this->images->store($image, Task::IMAGE_FOLDER));
        }

        $task = $this->tasks->save($task);
        $this->activities->add(TaskActivity::record($task, TaskAction::Created));

        return $task;
    }
}
