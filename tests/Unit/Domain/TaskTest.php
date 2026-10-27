<?php

namespace Tests\Unit\Domain;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\Exceptions\InvalidTaskException;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function test_new_task_is_trimmed_not_completed_and_owned_by_user(): void
    {
        $task = Task::create(7, new TaskDetails('  Học Laravel  '));

        $this->assertNull($task->id());
        $this->assertSame('Học Laravel', $task->title());
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->hasImage());
        $this->assertSame(TaskPriority::Medium, $task->priority());
        $this->assertSame(TaskCategory::Other, $task->category());
        $this->assertNull($task->dueDate());
        $this->assertTrue($task->belongsTo(7));
        $this->assertFalse($task->belongsTo(8));
    }

    public function test_task_keeps_category_priority_and_due_date_without_time(): void
    {
        $task = Task::create(1, new TaskDetails(
            'Họp',
            TaskPriority::High,
            TaskCategory::Work,
            new DateTimeImmutable('2026-11-02 15:30'),
        ));

        $this->assertSame(TaskPriority::High, $task->priority());
        $this->assertSame(TaskCategory::Work, $task->category());
        $this->assertSame('2026-11-02 00:00', $task->dueDate()->format('Y-m-d H:i'));
    }

    public function test_task_can_be_marked_completed_and_incomplete(): void
    {
        $task = Task::create(1, new TaskDetails('Đi chợ'));

        $task->markCompleted();
        $this->assertTrue($task->isCompleted());

        $task->markIncomplete();
        $this->assertFalse($task->isCompleted());
    }

    public function test_update_details_reports_whether_anything_changed(): void
    {
        $task = Task::create(1, new TaskDetails('Đi chợ', TaskPriority::Low, TaskCategory::Shopping));

        $this->assertFalse($task->updateDetails(new TaskDetails(' Đi chợ ', TaskPriority::Low, TaskCategory::Shopping)));
        $this->assertTrue($task->updateDetails(new TaskDetails('Đi siêu thị', TaskPriority::High, TaskCategory::Shopping, new DateTimeImmutable('2026-12-01'))));

        $this->assertSame('Đi siêu thị', $task->title());
        $this->assertSame(TaskPriority::High, $task->priority());
        $this->assertSame('2026-12-01', $task->dueDate()->format('Y-m-d'));
    }

    public function test_update_details_rejects_blank_title_and_keeps_old_values(): void
    {
        $task = Task::create(1, new TaskDetails('Đi chợ'));

        try {
            $task->updateDetails(new TaskDetails('   ', TaskPriority::High));
            $this->fail('Phải ném InvalidTaskException');
        } catch (InvalidTaskException) {
        }

        $this->assertSame('Đi chợ', $task->title());
        $this->assertSame(TaskPriority::Medium, $task->priority());
    }

    public function test_overdue_and_due_today(): void
    {
        $today = new DateTimeImmutable('2026-10-27');
        $yesterday = Task::create(1, new TaskDetails('A', dueDate: new DateTimeImmutable('2026-10-26')));
        $dueToday = Task::create(1, new TaskDetails('B', dueDate: new DateTimeImmutable('2026-10-27 23:00')));
        $noDue = Task::create(1, new TaskDetails('C'));

        $this->assertTrue($yesterday->isOverdue($today));
        $this->assertFalse($dueToday->isOverdue($today));
        $this->assertTrue($dueToday->isDueOn($today));
        $this->assertFalse($noDue->isOverdue($today));

        $yesterday->markCompleted();
        $this->assertFalse($yesterday->isOverdue($today), 'Việc đã xong thì không tính là quá hạn');
    }

    public function test_replace_image_returns_previous_path(): void
    {
        $task = Task::create(1, new TaskDetails('Chụp ảnh hóa đơn'));

        $this->assertNull($task->replaceImage('a.jpg'));
        $this->assertSame('a.jpg', $task->replaceImage('b.jpg'));
        $this->assertSame('b.jpg', $task->replaceImage(null));
        $this->assertFalse($task->hasImage());
    }

    public function test_blank_title_is_rejected(): void
    {
        $this->expectException(InvalidTaskException::class);

        Task::create(1, new TaskDetails('   '));
    }

    public function test_title_longer_than_limit_is_rejected(): void
    {
        $this->expectException(InvalidTaskException::class);

        Task::create(1, new TaskDetails(str_repeat('a', Task::MAX_TITLE_LENGTH + 1)));
    }

    public function test_title_limit_counts_multibyte_characters(): void
    {
        $title = str_repeat('ệ', Task::MAX_TITLE_LENGTH);

        $this->assertSame($title, Task::create(1, new TaskDetails($title))->title());
    }
}
