<?php

namespace App\Domain\Task\Entities;

use App\Domain\Task\ValueObjects\TaskAction;
use DateTimeImmutable;

/**
 * Một dòng lịch sử: người dùng đã làm gì với công việc nào, lúc nào.
 * Lưu kèm tên công việc để vẫn đọc được sau khi công việc bị xóa.
 */
final class TaskActivity
{
    private function __construct(
        private ?int $id,
        private int $userId,
        private ?int $taskId,
        private string $taskTitle,
        private TaskAction $action,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public static function record(Task $task, TaskAction $action): self
    {
        return new self(null, $task->userId(), $task->id(), $task->title(), $action, new DateTimeImmutable());
    }

    public static function restore(
        int $id,
        int $userId,
        ?int $taskId,
        string $taskTitle,
        TaskAction $action,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $userId, $taskId, $taskTitle, $action, $occurredAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function taskId(): ?int
    {
        return $this->taskId;
    }

    public function taskTitle(): string
    {
        return $this->taskTitle;
    }

    public function action(): TaskAction
    {
        return $this->action;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
