<?php

namespace Tests\Feature;

use App\Data\Task\Models\TaskModel;
use App\Data\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserModel::factory()->create();
        $this->actingAs($this->user);
    }

    private function taskFor(UserModel $user, array $attributes = []): TaskModel
    {
        return TaskModel::create(['user_id' => $user->id, 'title' => 'Việc mẫu', ...$attributes]);
    }

    public function test_page_renders_persisted_tasks_of_current_user_only(): void
    {
        $this->taskFor($this->user, ['title' => 'Việc của tôi']);
        $this->taskFor(UserModel::factory()->create(), ['title' => 'Việc người khác']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Bạn cần làm gì hôm nay?')
            ->assertSee($this->user->name)
            ->assertViewHas('tasks', fn (array $tasks) => count($tasks) === 1 && $tasks[0]['title'] === 'Việc của tôi');
    }

    public function test_can_list_own_tasks_newest_first(): void
    {
        $this->taskFor($this->user, ['title' => 'Cũ']);
        $this->taskFor($this->user, ['title' => 'Mới', 'is_completed' => true]);
        $this->taskFor(UserModel::factory()->create(), ['title' => 'Của người khác']);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Mới')
            ->assertJsonPath('data.0.completed', true)
            ->assertJsonPath('data.0.image_url', null)
            ->assertJsonPath('data.1.title', 'Cũ');
    }

    public function test_can_create_task(): void
    {
        $this->postJson('/api/tasks', ['title' => '  Mua sữa  '])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Mua sữa')
            ->assertJsonPath('data.completed', false);

        $this->assertDatabaseHas('tasks', ['user_id' => $this->user->id, 'title' => 'Mua sữa', 'is_completed' => false]);
        $this->assertDatabaseHas('task_activities', ['user_id' => $this->user->id, 'task_title' => 'Mua sữa', 'action' => 'created']);
    }

    public function test_create_requires_valid_title(): void
    {
        $this->postJson('/api/tasks', ['title' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        $this->postJson('/api/tasks', ['title' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_can_mark_task_completed_and_incomplete(): void
    {
        $task = $this->taskFor($this->user);

        $this->patchJson("/api/tasks/{$task->id}", ['completed' => true])
            ->assertOk()
            ->assertJsonPath('data.completed', true);
        $this->assertTrue($task->fresh()->is_completed);

        $this->patchJson("/api/tasks/{$task->id}", ['completed' => false])
            ->assertOk()
            ->assertJsonPath('data.completed', false);
        $this->assertFalse($task->fresh()->is_completed);

        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'completed']);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'reopened']);
    }

    public function test_update_requires_boolean_status(): void
    {
        $task = $this->taskFor($this->user);

        $this->patchJson("/api/tasks/{$task->id}", ['completed' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('completed');
    }

    public function test_can_delete_task(): void
    {
        $task = $this->taskFor($this->user, ['title' => 'Dọn nhà']);

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('task_activities', ['task_title' => 'Dọn nhà', 'action' => 'deleted']);
    }

    public function test_missing_task_returns_404(): void
    {
        $this->patchJson('/api/tasks/999', ['completed' => true])->assertNotFound();
        $this->deleteJson('/api/tasks/999')->assertNotFound();
    }

    public function test_cannot_modify_another_users_task(): void
    {
        $task = $this->taskFor(UserModel::factory()->create(), ['title' => 'Của người khác']);

        $this->patchJson("/api/tasks/{$task->id}", ['completed' => true])->assertNotFound();
        $this->deleteJson("/api/tasks/{$task->id}")->assertNotFound();
        $this->deleteJson("/api/tasks/{$task->id}/image")->assertNotFound();

        $this->assertFalse($task->fresh()->is_completed);
        $this->assertDatabaseCount('task_activities', 0);
    }
}
