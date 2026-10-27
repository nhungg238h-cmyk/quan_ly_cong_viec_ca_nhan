<?php

namespace App\Data\Task\Repositories;

use App\Data\Task\Models\TaskActivityModel;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;

final class EloquentTaskActivityRepository implements TaskActivityRepositoryInterface
{
    public function add(TaskActivity $activity): void
    {
        TaskActivityModel::create([
            'user_id' => $activity->userId(),
            'task_id' => $activity->taskId(),
            'task_title' => $activity->taskTitle(),
            'action' => $activity->action()->value,
            'created_at' => $activity->occurredAt(),
        ]);
    }

    public function latestForUser(int $userId, int $limit): array
    {
        return TaskActivityModel::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (TaskActivityModel $model) => TaskActivity::restore(
                $model->id,
                $model->user_id,
                $model->task_id,
                $model->task_title,
                TaskAction::from($model->action),
                $model->created_at->toDateTimeImmutable(),
            ))
            ->all();
    }

    public function countPerDay(int $userId, TaskAction $action, DateTimeImmutable $since): array
    {
        // Gom nhóm bằng PHP để chạy giống nhau trên MySQL và SQLite (dùng khi test).
        return TaskActivityModel::query()
            ->where('user_id', $userId)
            ->where('action', $action->value)
            ->where('created_at', '>=', $since)
            ->pluck('created_at')
            ->countBy(fn ($createdAt) => $createdAt->format('Y-m-d'))
            ->all();
    }
}
