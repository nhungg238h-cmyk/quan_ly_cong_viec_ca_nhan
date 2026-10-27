<?php

namespace App\Domain\Task\UseCases;

use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;

/**
 * Số lượt hoàn thành công việc theo từng ngày trong N ngày gần nhất (kể cả hôm nay).
 */
final class GetCompletionTrendUseCase
{
    public function __construct(private TaskActivityRepositoryInterface $activities)
    {
    }

    /**
     * @return array<string, int> Khóa là ngày 'Y-m-d', cũ nhất trước; ngày không có thì là 0.
     */
    public function execute(int $userId, DateTimeImmutable $today, int $days = 7): array
    {
        $first = $today->setTime(0, 0)->modify('-'.($days - 1).' days');
        $counts = $this->activities->countPerDay($userId, TaskAction::Completed, $first);

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $first->modify("+{$i} days")->format('Y-m-d');
            $trend[$day] = $counts[$day] ?? 0;
        }

        return $trend;
    }
}
