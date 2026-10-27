<?php

namespace Tests\Feature;

use App\Data\Task\Repositories\EloquentTaskActivityRepository;
use App\Data\Task\Repositories\EloquentTaskRepository;
use App\Data\User\Models\UserModel;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\Entities\TaskActivity;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskAction;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentTaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepositoryInterface $repository;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(TaskRepositoryInterface::class);
        $this->userId = UserModel::factory()->create()->id;
    }

    public function test_container_binds_interfaces_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentTaskRepository::class, $this->repository);
        $this->assertInstanceOf(EloquentTaskActivityRepository::class, $this->app->make(TaskActivityRepositoryInterface::class));
    }

    public function test_save_inserts_new_task_and_assigns_id(): void
    {
        $saved = $this->repository->save(Task::create($this->userId, new TaskDetails('Học Laravel')));

        $this->assertNotNull($saved->id());
        $this->assertDatabaseHas('tasks', ['id' => $saved->id(), 'user_id' => $this->userId, 'title' => 'Học Laravel', 'is_completed' => false]);
    }

    public function test_save_updates_existing_task_without_duplicating(): void
    {
        $task = $this->repository->save(Task::create($this->userId, new TaskDetails('Đi chợ')));
        $task->markCompleted();
        $task->replaceImage('task-images/a.jpg');
        $task->updateDetails(new TaskDetails('Đi siêu thị', TaskPriority::High, TaskCategory::Shopping, new DateTimeImmutable('2026-11-05')));

        $this->repository->save($task);

        $this->assertDatabaseCount('tasks', 1);
        $found = $this->repository->findForUser($task->id(), $this->userId);
        $this->assertTrue($found->isCompleted());
        $this->assertSame('task-images/a.jpg', $found->imagePath());
        $this->assertSame('Đi siêu thị', $found->title());
        $this->assertSame(TaskPriority::High, $found->priority());
        $this->assertSame(TaskCategory::Shopping, $found->category());
        $this->assertSame('2026-11-05', $found->dueDate()->format('Y-m-d'));
    }

    public function test_find_only_returns_tasks_of_the_owner(): void
    {
        $task = $this->repository->save(Task::create($this->userId, new TaskDetails('Của tôi')));
        $otherUserId = UserModel::factory()->create()->id;

        $this->assertNull($this->repository->findForUser($task->id(), $otherUserId));
        $this->assertNull($this->repository->findForUser(999, $this->userId));
        $this->assertSame([], $this->repository->allForUser($otherUserId));
    }

    public function test_all_returns_entities_newest_first(): void
    {
        $this->repository->save(Task::create($this->userId, new TaskDetails('Việc 1')));
        $this->repository->save(Task::create($this->userId, new TaskDetails('Việc 2')));

        $tasks = $this->repository->allForUser($this->userId);

        $this->assertContainsOnlyInstancesOf(Task::class, $tasks);
        $this->assertSame(['Việc 2', 'Việc 1'], array_map(fn (Task $t) => $t->title(), $tasks));
    }

    public function test_delete_removes_task(): void
    {
        $task = $this->repository->save(Task::create($this->userId, new TaskDetails('Dọn nhà')));

        $this->repository->delete($task->id());

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_activity_repository_round_trips_and_limits(): void
    {
        $activities = $this->app->make(TaskActivityRepositoryInterface::class);
        $task = $this->repository->save(Task::create($this->userId, new TaskDetails('Việc')));

        $activities->add(TaskActivity::record($task, TaskAction::Created));
        $activities->add(TaskActivity::record($task, TaskAction::Completed));

        $latest = $activities->latestForUser($this->userId, 1);

        $this->assertCount(1, $latest);
        $this->assertSame(TaskAction::Completed, $latest[0]->action());
        $this->assertSame($task->id(), $latest[0]->taskId());
        $this->assertSame('Việc', $latest[0]->taskTitle());

        $today = (new DateTimeImmutable())->format('Y-m-d');
        $this->assertSame([$today => 1], $activities->countPerDay($this->userId, TaskAction::Completed, new DateTimeImmutable('today')));
        $this->assertSame([], $activities->countPerDay($this->userId, TaskAction::Completed, new DateTimeImmutable('tomorrow')));
    }
}
