<?php

namespace Tests\Support;

use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;

final class InMemoryTaskActivityRepository implements TaskActivityRepositoryInterface
{
    /** @var TaskActivity[] */
    public array $activities = [];

    public function add(TaskActivity $activity): void
    {
        $this->activities[] = $activity;
    }

    public function latestForUser(int $userId, int $limit): array
    {
        $mine = array_filter(array_reverse($this->activities), fn (TaskActivity $a) => $a->userId() === $userId);

        return array_slice(array_values($mine), 0, $limit);
    }

    public function countPerDay(int $userId, TaskAction $action, DateTimeImmutable $since): array
    {
        $counts = [];
        foreach ($this->activities as $a) {
            if ($a->userId() === $userId && $a->action() === $action && $a->occurredAt() >= $since) {
                $day = $a->occurredAt()->format('Y-m-d');
                $counts[$day] = ($counts[$day] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return TaskAction[] Theo thứ tự đã ghi.
     */
    public function actions(): array
    {
        return array_map(fn (TaskActivity $a) => $a->action(), $this->activities);
    }
}
