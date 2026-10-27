<?php

namespace App\Domain\Task\ValueObjects;

use DateTimeImmutable;

/**
 * Các thông tin người dùng nhập cho một công việc (dùng khi thêm và khi sửa).
 */
final class TaskDetails
{
    public function __construct(
        public readonly string $title,
        public readonly TaskPriority $priority = TaskPriority::Medium,
        public readonly TaskCategory $category = TaskCategory::Other,
        public readonly ?DateTimeImmutable $dueDate = null,
    ) {
    }
}
