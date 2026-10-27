<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;

interface TaskActivityRepositoryInterface
{
    public function add(TaskActivity $activity): void;

    /**
     * @return TaskActivity[] Mới nhất trước.
     */
    public function latestForUser(int $userId, int $limit): array;

    /**
     * Đếm số lần một thao tác xảy ra theo ngày, từ ngày $since đến nay.
     *
     * @return array<string, int> Khóa là ngày 'Y-m-d'.
     */
    public function countPerDay(int $userId, TaskAction $action, DateTimeImmutable $since): array;
}
