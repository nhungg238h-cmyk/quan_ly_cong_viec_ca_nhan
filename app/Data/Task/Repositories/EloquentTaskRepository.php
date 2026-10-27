<?php

namespace App\Data\Task\Repositories;

use App\Data\Task\Models\TaskModel;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskPriority;

/**
 * Hiện thực TaskRepositoryInterface bằng Eloquent (MySQL).
 */
final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function allForUser(int $userId): array
    {
        return TaskModel::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (TaskModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findForUser(int $id, int $userId): ?Task
    {
        $model = TaskModel::where('user_id', $userId)->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Task $task): Task
    {
        $model = $task->id() === null ? new TaskModel() : TaskModel::findOrFail($task->id());

        $model->user_id = $task->userId();
        $model->title = $task->title();
        $model->is_completed = $task->isCompleted();
        $model->image_path = $task->imagePath();
        $model->priority = $task->priority()->value;
        $model->category = $task->category()->value;
        $model->due_date = $task->dueDate()?->format('Y-m-d');
        $model->save();

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        TaskModel::whereKey($id)->delete();
    }

    private function toEntity(TaskModel $model): Task
    {
        return Task::restore(
            $model->id,
            $model->user_id,
            $model->title,
            $model->is_completed,
            $model->image_path,
            TaskPriority::from($model->priority),
            TaskCategory::from($model->category),
            $model->due_date?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
