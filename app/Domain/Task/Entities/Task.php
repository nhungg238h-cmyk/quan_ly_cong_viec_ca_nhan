<?php

namespace App\Domain\Task\Entities;

use App\Domain\Task\Exceptions\InvalidTaskException;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use DateTimeImmutable;

/**
 * Thực thể Công việc - thuần PHP, không phụ thuộc framework.
 */
final class Task
{
    public const MAX_TITLE_LENGTH = 255;

    public const IMAGE_FOLDER = 'task-images';

    private function __construct(
        private ?int $id,
        private int $userId,
        private string $title,
        private bool $completed,
        private ?string $imagePath,
        private TaskPriority $priority,
        private TaskCategory $category,
        private ?DateTimeImmutable $dueDate,
        private DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Tạo một công việc mới (chưa lưu, chưa có id) cho người dùng.
     */
    public static function create(int $userId, TaskDetails $details): self
    {
        return new self(
            null,
            $userId,
            self::normalizeTitle($details->title),
            false,
            null,
            $details->priority,
            $details->category,
            self::dateOnly($details->dueDate),
            new DateTimeImmutable(),
        );
    }

    /**
     * Khôi phục công việc đã lưu từ tầng dữ liệu.
     */
    public static function restore(
        int $id,
        int $userId,
        string $title,
        bool $completed,
        ?string $imagePath,
        TaskPriority $priority,
        TaskCategory $category,
        ?DateTimeImmutable $dueDate,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $userId, $title, $completed, $imagePath, $priority, $category, self::dateOnly($dueDate), $createdAt);
    }

    /**
     * Sửa thông tin công việc; trả về true nếu có gì thay đổi.
     */
    public function updateDetails(TaskDetails $details): bool
    {
        $title = self::normalizeTitle($details->title);
        $dueDate = self::dateOnly($details->dueDate);

        $changed = $title !== $this->title
            || $details->priority !== $this->priority
            || $details->category !== $this->category
            || $dueDate?->format('Y-m-d') !== $this->dueDate?->format('Y-m-d');

        $this->title = $title;
        $this->priority = $details->priority;
        $this->category = $details->category;
        $this->dueDate = $dueDate;

        return $changed;
    }

    public function markCompleted(): void
    {
        $this->completed = true;
    }

    public function markIncomplete(): void
    {
        $this->completed = false;
    }

    /**
     * Gắn ảnh mới (hoặc gỡ ảnh khi truyền null), trả về đường dẫn ảnh cũ để dọn dẹp.
     */
    public function replaceImage(?string $imagePath): ?string
    {
        $previous = $this->imagePath;
        $this->imagePath = $imagePath;

        return $previous;
    }

    public function belongsTo(int $userId): bool
    {
        return $this->userId === $userId;
    }

    /**
     * Quá hạn: chưa xong và hạn chót đã qua (tính theo ngày).
     */
    public function isOverdue(DateTimeImmutable $today): bool
    {
        return ! $this->completed
            && $this->dueDate !== null
            && $this->dueDate->format('Y-m-d') < $today->format('Y-m-d');
    }

    public function isDueOn(DateTimeImmutable $day): bool
    {
        return $this->dueDate?->format('Y-m-d') === $day->format('Y-m-d');
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function imagePath(): ?string
    {
        return $this->imagePath;
    }

    public function hasImage(): bool
    {
        return $this->imagePath !== null;
    }

    public function priority(): TaskPriority
    {
        return $this->priority;
    }

    public function category(): TaskCategory
    {
        return $this->category;
    }

    public function dueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function normalizeTitle(string $title): string
    {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidTaskException('Tên công việc không được để trống.');
        }

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidTaskException('Tên công việc không được dài quá '.self::MAX_TITLE_LENGTH.' ký tự.');
        }

        return $title;
    }

    private static function dateOnly(?DateTimeImmutable $date): ?DateTimeImmutable
    {
        return $date?->setTime(0, 0);
    }
}
