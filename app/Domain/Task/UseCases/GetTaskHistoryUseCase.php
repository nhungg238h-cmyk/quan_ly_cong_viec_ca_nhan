<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;

final class GetTaskHistoryUseCase
{
    public const DEFAULT_LIMIT = 200;

    public function __construct(private TaskActivityRepositoryInterface $activities)
    {
    }

    /**
     * @return TaskActivity[]
     */
    public function execute(int $userId, int $limit = self::DEFAULT_LIMIT): array
    {
        return $this->activities->latestForUser($userId, $limit);
    }
}
