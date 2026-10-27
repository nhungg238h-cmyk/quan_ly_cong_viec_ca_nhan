<?php

namespace Tests\Feature;

use App\Data\Task\Models\TaskModel;
use App\Data\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Danh mục, mức ưu tiên, hạn chót, sửa công việc, xóa các việc đã xong.
 */
class TaskFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserModel::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_task_with_category_priority_and_due_date(): void
    {
        $this->postJson('/api/tasks', [
            'title' => 'Báo cáo quý',
            'category' => 'work',
            'priority' => 'high',
            'due_date' => '2026-11-15',
        ])
            ->assertCreated()
            ->assertJsonPath('data.category', 'work')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-11-15');

        $this->assertDatabaseHas('tasks', ['title' => 'Báo cáo quý', 'category' => 'work', 'priority' => 'high']);
    }

    public function test_defaults_when_details_are_omitted(): void
    {
        $this->postJson('/api/tasks', ['title' => 'Việc đơn giản'])
            ->assertCreated()
            ->assertJsonPath('data.category', 'other')
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.due_date', null);
    }

    public function test_rejects_invalid_details(): void
    {
        $this->postJson('/api/tasks', [
            'title' => 'Sai',
            'category' => 'khong-co',
            'priority' => 'gap',
            'due_date' => '15/11/2026',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'priority', 'due_date']);
    }

    public function test_create_with_details_and_image_in_one_multipart_request(): void
    {
        Storage::fake('local');

        $this->post('/api/tasks', [
            'title' => 'Hóa đơn',
            'category' => 'shopping',
            'priority' => 'low',
            'image' => UploadedFile::fake()->image('hoa-don.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.category', 'shopping')
            ->assertJsonPath('data.image_url', fn ($url) => $url !== null);
    }

    public function test_can_edit_task(): void
    {
        $task = TaskModel::create(['user_id' => $this->user->id, 'title' => 'Đi chợ']);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Đi siêu thị',
            'category' => 'shopping',
            'priority' => 'high',
            'due_date' => '2026-11-20',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Đi siêu thị')
            ->assertJsonPath('data.category', 'shopping')
            ->assertJsonPath('data.due_date', '2026-11-20');

        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'updated', 'task_title' => 'Đi siêu thị']);

        $this->putJson("/api/tasks/{$task->id}", ['title' => 'Đi siêu thị', 'category' => 'shopping', 'priority' => 'high', 'due_date' => null])
            ->assertOk()
            ->assertJsonPath('data.due_date', null);
    }

    public function test_edit_validates_and_protects_other_users_tasks(): void
    {
        $task = TaskModel::create(['user_id' => $this->user->id, 'title' => 'Đi chợ']);
        $foreign = TaskModel::create(['user_id' => UserModel::factory()->create()->id, 'title' => 'Của người khác']);

        $this->putJson("/api/tasks/{$task->id}", ['title' => ''])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->putJson("/api/tasks/{$foreign->id}", ['title' => 'Hack'])->assertNotFound();

        $this->assertSame('Của người khác', $foreign->fresh()->title);
    }

    public function test_clear_completed_only_removes_own_completed_tasks(): void
    {
        TaskModel::create(['user_id' => $this->user->id, 'title' => 'Xong 1', 'is_completed' => true]);
        TaskModel::create(['user_id' => $this->user->id, 'title' => 'Xong 2', 'is_completed' => true]);
        TaskModel::create(['user_id' => $this->user->id, 'title' => 'Chưa xong']);
        $foreign = TaskModel::create(['user_id' => UserModel::factory()->create()->id, 'title' => 'Của người khác', 'is_completed' => true]);

        $this->deleteJson('/api/tasks/completed')
            ->assertOk()
            ->assertJsonPath('data.deleted', 2);

        $this->assertSame(['Chưa xong'], TaskModel::where('user_id', $this->user->id)->pluck('title')->all());
        $this->assertModelExists($foreign);
        $this->assertDatabaseCount('task_activities', 2);
    }

    public function test_page_includes_options_for_categories_and_priorities(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Công việc')
            ->assertSee('Ưu tiên cao')
            ->assertViewHas('options', fn (array $o) => isset($o['categories']['health'], $o['priorities']['low']));
    }
}
