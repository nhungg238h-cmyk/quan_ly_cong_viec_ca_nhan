<?php

namespace App\Presentation\Http\Controllers;

use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\UseCases\GetCompletionTrendUseCase;
use App\Domain\Task\UseCases\GetTaskHistoryUseCase;
use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trang lịch sử thao tác (nhóm theo ngày) + biểu đồ hoàn thành 7 ngày.
 */
class HistoryController
{
    private const ACTIONS = [
        'created' => ['label' => 'Đã thêm công việc', 'icon' => 'plus'],
        'updated' => ['label' => 'Đã sửa công việc', 'icon' => 'pencil'],
        'completed' => ['label' => 'Đã hoàn thành', 'icon' => 'check'],
        'reopened' => ['label' => 'Đánh dấu chưa hoàn thành', 'icon' => 'rotate'],
        'image_updated' => ['label' => 'Đã cập nhật ảnh cho', 'icon' => 'image'],
        'image_removed' => ['label' => 'Đã gỡ ảnh khỏi', 'icon' => 'image-off'],
        'deleted' => ['label' => 'Đã xóa công việc', 'icon' => 'trash'],
    ];

    private const WEEKDAYS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

    public function __invoke(Request $request, GetTaskHistoryUseCase $getHistory, GetCompletionTrendUseCase $getTrend): View
    {
        $userId = $request->user()->id;
        $today = new DateTimeImmutable('today');
        $activities = $getHistory->execute($userId);

        $groups = [];
        foreach ($activities as $activity) {
            $groups[$this->dayLabel($activity->occurredAt(), $today)][] = $this->present($activity);
        }

        $counts = array_count_values(array_map(fn (TaskActivity $a) => $a->action()->value, $activities));
        $trend = $getTrend->execute($userId, $today);

        return view('history.index', [
            'groups' => $groups,
            'stats' => [
                ['label' => 'Đã thêm', 'value' => $counts[TaskAction::Created->value] ?? 0],
                ['label' => 'Đã hoàn thành', 'value' => $counts[TaskAction::Completed->value] ?? 0],
                ['label' => 'Đã sửa', 'value' => $counts[TaskAction::Updated->value] ?? 0],
                ['label' => 'Đã xóa', 'value' => $counts[TaskAction::Deleted->value] ?? 0],
            ],
            'trend' => $this->presentTrend($trend, $today),
            'trendTotal' => array_sum($trend),
            'limit' => GetTaskHistoryUseCase::DEFAULT_LIMIT,
            'total' => count($activities),
        ]);
    }

    private function present(TaskActivity $activity): array
    {
        $action = $activity->action()->value;

        return [
            'action' => $action,
            'label' => self::ACTIONS[$action]['label'],
            'icon' => self::ACTIONS[$action]['icon'],
            'title' => $activity->taskTitle(),
            'time' => $activity->occurredAt()->format('H:i'),
            'datetime' => $activity->occurredAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @param  array<string, int>  $trend
     */
    private function presentTrend(array $trend, DateTimeImmutable $today): array
    {
        $max = max(1, ...array_values($trend));

        return array_map(fn (string $day, int $count) => [
            'label' => $day === $today->format('Y-m-d') ? 'Nay' : self::WEEKDAYS[(int) date('w', strtotime($day))],
            'date' => date('d/m', strtotime($day)),
            'count' => $count,
            'percent' => (int) round($count / $max * 100),
            'is_today' => $day === $today->format('Y-m-d'),
        ], array_keys($trend), $trend);
    }

    private function dayLabel(DateTimeImmutable $date, DateTimeImmutable $today): string
    {
        return match ($date->format('Y-m-d')) {
            $today->format('Y-m-d') => 'Hôm nay',
            $today->modify('-1 day')->format('Y-m-d') => 'Hôm qua',
            default => $date->format('d/m/Y'),
        };
    }
}
