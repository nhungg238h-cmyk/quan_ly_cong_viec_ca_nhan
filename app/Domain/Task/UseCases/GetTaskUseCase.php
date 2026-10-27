<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

final class GetTaskUseCase
{
    public function __construct(private TaskRepositoryInterface $tasks)
    {
    }

    public function execute(int $userId, int $id): Task
    {
        return $this->tasks->findForUser($id, $userId) ?? throw TaskNotFoundException::withId($id);
    }
}
