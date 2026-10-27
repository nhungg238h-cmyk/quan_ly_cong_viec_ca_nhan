<?php

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\Task\Exceptions\InvalidTaskException;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\Task\UseCases\ClearCompletedTasksUseCase;
use App\Domain\Task\UseCases\CreateTaskUseCase;
use App\Domain\Task\UseCases\DeleteTaskUseCase;
use App\Domain\Task\UseCases\GetCompletionTrendUseCase;
use App\Domain\Task\UseCases\GetTaskHistoryUseCase;
use App\Domain\Task\UseCases\GetTasksUseCase;
use App\Domain\Task\UseCases\GetTaskUseCase;
use App\Domain\Task\UseCases\SetTaskCompletionUseCase;
use App\Domain\Task\UseCases\SetTaskImageUseCase;
use App\Domain\Task\UseCases\UpdateTaskUseCase;
use App\Domain\Task\ValueObjects\TaskAction;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeImageStorage;
use Tests\Support\InMemoryTaskActivityRepository;
use Tests\Support\InMemoryTaskRepository;

class TaskUseCasesTest extends TestCase
{
    private const ALICE = 1;
    private const BOB = 2;

    private InMemoryTaskRepository $tasks;
    private InMemoryTaskActivityRepository $activities;
    private FakeImageStorage $images;

    protected function setUp(): void
    {
        $this->tasks = new InMemoryTaskRepository();
        $this->activities = new InMemoryTaskActivityRepository();
        $this->images = new FakeImageStorage();
    }

    private function create(int $userId, string $title, ?ImageUpload $image = null)
    {
        return (new CreateTaskUseCase($this->tasks, $this->images, $this->activities))
            ->execute($userId, new TaskDetails($title), $image);
    }

    private function setCompletion(): SetTaskCompletionUseCase
    {
        return new SetTaskCompletionUseCase($this->tasks, $this->activities);
    }

    private function setImage(): SetTaskImageUseCase
    {
        return new SetTaskImageUseCase($this->tasks, $this->images, $this->activities);
    }

    private function delete(): DeleteTaskUseCase
    {
        return new DeleteTaskUseCase($this->tasks, $this->images, $this->activities);
    }

    private function update(): UpdateTaskUseCase
    {
        return new UpdateTaskUseCase($this->tasks, $this->activities);
    }

    private function image(): ImageUpload
    {
        return new ImageUpload('/tmp/upload', 'jpg');
    }

    public function test_create_task_persists_details_and_records_history(): void
    {
        $task = (new CreateTaskUseCase($this->tasks, $this->images, $this->activities))->execute(
            self::ALICE,
            new TaskDetails('Viết báo cáo', TaskPriority::High, TaskCategory::Work, new DateTimeImmutable('2026-11-01')),
        );

        $saved = $this->tasks->findForUser($task->id(), self::ALICE);
        $this->assertSame('Viết báo cáo', $saved->title());
        $this->assertSame(TaskPriority::High, $saved->priority());
        $this->assertSame(TaskCategory::Work, $saved->category());
        $this->assertSame('2026-11-01', $saved->dueDate()->format('Y-m-d'));
        $this->assertEquals([TaskAction::Created], $this->activities->actions());
    }

    public function test_create_task_with_image_stores_it_in_task_folder(): void
    {
        $task = $this->create(self::ALICE, 'Hóa đơn điện', $this->image());

        $this->assertTrue($task->hasImage());
        $this->assertStringStartsWith('task-images/', $task->imagePath());
        $this->assertArrayHasKey($task->imagePath(), $this->images->stored);
    }

    public function test_invalid_title_does_not_store_image_or_history(): void
    {
        try {
            $this->create(self::ALICE, '  ', $this->image());
            $this->fail('Phải ném InvalidTaskException');
        } catch (InvalidTaskException) {
        }

        $this->assertSame([], $this->images->stored);
        $this->assertSame([], $this->activities->activities);
    }

    public function test_users_only_see_their_own_tasks_newest_first(): void
    {
        $this->create(self::ALICE, 'Việc 1');
        $this->create(self::BOB, 'Việc của Bob');
        $this->create(self::ALICE, 'Việc 2');

        $titles = array_map(fn ($t) => $t->title(), (new GetTasksUseCase($this->tasks))->execute(self::ALICE));

        $this->assertSame(['Việc 2', 'Việc 1'], $titles);
    }

    public function test_update_task_saves_changes_and_records_history_only_when_changed(): void
    {
        $task = $this->create(self::ALICE, 'Đi chợ');

        $updated = $this->update()->execute(self::ALICE, $task->id(), new TaskDetails('Đi siêu thị', TaskPriority::High, TaskCategory::Shopping));
        $this->update()->execute(self::ALICE, $task->id(), new TaskDetails('Đi siêu thị', TaskPriority::High, TaskCategory::Shopping));

        $this->assertSame('Đi siêu thị', $updated->title());
        $this->assertSame(TaskCategory::Shopping, $this->tasks->findForUser($task->id(), self::ALICE)->category());
        $this->assertEquals([TaskAction::Created, TaskAction::Updated], $this->activities->actions());
    }

    public function test_set_completion_toggles_both_ways_and_records_history(): void
    {
        $task = $this->create(self::ALICE, 'Tập thể dục');

        $this->assertTrue($this->setCompletion()->execute(self::ALICE, $task->id(), true)->isCompleted());
        $this->assertFalse($this->setCompletion()->execute(self::ALICE, $task->id(), false)->isCompleted());

        $this->assertEquals(
            [TaskAction::Created, TaskAction::Completed, TaskAction::Reopened],
            $this->activities->actions(),
        );
    }

    public function test_set_completion_to_same_state_records_nothing(): void
    {
        $task = $this->create(self::ALICE, 'Tập thể dục');

        $this->setCompletion()->execute(self::ALICE, $task->id(), false);

        $this->assertEquals([TaskAction::Created], $this->activities->actions());
    }

    public function test_replacing_image_deletes_the_old_file(): void
    {
        $task = $this->create(self::ALICE, 'Ảnh', $this->image());
        $oldPath = $task->imagePath();

        $updated = $this->setImage()->execute(self::ALICE, $task->id(), $this->image());

        $this->assertNotSame($oldPath, $updated->imagePath());
        $this->assertArrayNotHasKey($oldPath, $this->images->stored);
        $this->assertArrayHasKey($updated->imagePath(), $this->images->stored);
        $this->assertEquals(TaskAction::ImageUpdated, end($this->activities->activities)->action());
    }

    public function test_removing_image_deletes_file_and_records_history(): void
    {
        $task = $this->create(self::ALICE, 'Ảnh', $this->image());

        $updated = $this->setImage()->execute(self::ALICE, $task->id(), null);

        $this->assertFalse($updated->hasImage());
        $this->assertSame([], $this->images->stored);
        $this->assertEquals(TaskAction::ImageRemoved, end($this->activities->activities)->action());
    }

    public function test_removing_missing_image_records_nothing(): void
    {
        $task = $this->create(self::ALICE, 'Không có ảnh');

        $this->setImage()->execute(self::ALICE, $task->id(), null);

        $this->assertEquals([TaskAction::Created], $this->activities->actions());
    }

    public function test_delete_removes_task_and_its_image_and_keeps_history(): void
    {
        $task = $this->create(self::ALICE, 'Đọc sách', $this->image());

        $this->delete()->execute(self::ALICE, $task->id());

        $this->assertNull($this->tasks->findForUser($task->id(), self::ALICE));
        $this->assertSame([], $this->images->stored);

        $history = (new GetTaskHistoryUseCase($this->activities))->execute(self::ALICE);
        $this->assertEquals(TaskAction::Deleted, $history[0]->action());
        $this->assertSame('Đọc sách', $history[0]->taskTitle());
    }

    public function test_clear_completed_removes_only_own_completed_tasks(): void
    {
        $done = $this->create(self::ALICE, 'Xong rồi', $this->image());
        $this->create(self::ALICE, 'Chưa xong');
        $bobDone = $this->create(self::BOB, 'Của Bob');
        $this->setCompletion()->execute(self::ALICE, $done->id(), true);
        $this->setCompletion()->execute(self::BOB, $bobDone->id(), true);

        $deleted = (new ClearCompletedTasksUseCase($this->tasks, $this->delete()))->execute(self::ALICE);

        $this->assertSame(1, $deleted);
        $this->assertSame(['Chưa xong'], array_map(fn ($t) => $t->title(), $this->tasks->allForUser(self::ALICE)));
        $this->assertCount(1, $this->tasks->allForUser(self::BOB));
        $this->assertSame([], $this->images->stored);
        $this->assertEquals(TaskAction::Deleted, end($this->activities->activities)->action());
    }

    public function test_completion_trend_fills_every_day_including_zeros(): void
    {
        $task = $this->create(self::ALICE, 'Việc');
        $this->setCompletion()->execute(self::ALICE, $task->id(), true);
        $this->setCompletion()->execute(self::ALICE, $task->id(), false);
        $this->setCompletion()->execute(self::ALICE, $task->id(), true);

        $today = new DateTimeImmutable('today');
        $trend = (new GetCompletionTrendUseCase($this->activities))->execute(self::ALICE, $today);

        $this->assertCount(7, $trend);
        $this->assertSame($today->modify('-6 days')->format('Y-m-d'), array_key_first($trend));
        $this->assertSame(2, $trend[$today->format('Y-m-d')]);
        $this->assertSame(2, array_sum($trend));
        $this->assertSame(0, (new GetCompletionTrendUseCase($this->activities))->execute(self::BOB, $today)[$today->format('Y-m-d')]);
    }

    public function test_history_is_private_to_each_user(): void
    {
        $this->create(self::ALICE, 'Của Alice');
        $this->create(self::BOB, 'Của Bob');

        $history = (new GetTaskHistoryUseCase($this->activities))->execute(self::BOB);

        $this->assertCount(1, $history);
        $this->assertSame('Của Bob', $history[0]->taskTitle());
    }

    public function test_other_users_cannot_touch_a_task(): void
    {
        $task = $this->create(self::ALICE, 'Bí mật');

        $attempts = [
            fn () => (new GetTaskUseCase($this->tasks))->execute(self::BOB, $task->id()),
            fn () => $this->update()->execute(self::BOB, $task->id(), new TaskDetails('Hack')),
            fn () => $this->setCompletion()->execute(self::BOB, $task->id(), true),
            fn () => $this->setImage()->execute(self::BOB, $task->id(), $this->image()),
            fn () => $this->delete()->execute(self::BOB, $task->id()),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Phải ném TaskNotFoundException');
            } catch (TaskNotFoundException) {
            }
        }

        $saved = $this->tasks->findForUser($task->id(), self::ALICE);
        $this->assertSame('Bí mật', $saved->title());
        $this->assertFalse($saved->isCompleted());
        $this->assertEquals([TaskAction::Created], $this->activities->actions());
    }

    public function test_missing_task_throws(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $this->delete()->execute(self::ALICE, 999);
    }
}
