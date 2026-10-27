<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

final class GetTasksUseCase
{
    public function __construct(private TaskRepositoryInterface $tasks)
    {
    }

    /**
     * @return Task[]
     */
    public function execute(int $userId): array
    {
        return $this->tasks->allForUser($userId);
    }
}
