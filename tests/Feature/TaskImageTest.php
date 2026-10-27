<?php

namespace Tests\Feature;

use App\Data\Task\Models\TaskModel;
use App\Data\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskImageTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->user = UserModel::factory()->create();
        $this->actingAs($this->user);
    }

    private function createTaskWithImage(string $title = 'Có ảnh'): array
    {
        return $this->post('/api/tasks', [
            'title' => $title,
            'image' => UploadedFile::fake()->image('anh.jpg', 640, 480),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
    }

    public function test_can_create_task_with_image_and_view_it(): void
    {
        $data = $this->createTaskWithImage();
        $path = TaskModel::find($data['id'])->image_path;

        $this->assertNotNull($data['image_url']);
        Storage::disk('local')->assertExists($path);

        $response = $this->get($data['image_url'])
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));

        // Trình duyệt hỏi lại với ETag: chủ sở hữu nhận 304, không phải tải lại ảnh.
        $this->get($data['image_url'], ['If-None-Match' => $response->headers->get('ETag')])
            ->assertStatus(304);
    }

    public function test_can_add_image_to_existing_task_and_replace_it(): void
    {
        $task = TaskModel::create(['user_id' => $this->user->id, 'title' => 'Chưa có ảnh']);

        $first = $this->post("/api/tasks/{$task->id}/image", [
            'image' => UploadedFile::fake()->image('a.png'),
        ], ['Accept' => 'application/json'])->assertOk()->json('data');
        $firstPath = $task->fresh()->image_path;

        $second = $this->post("/api/tasks/{$task->id}/image", [
            'image' => UploadedFile::fake()->image('b.png'),
        ], ['Accept' => 'application/json'])->assertOk()->json('data');

        $this->assertNotSame($first['image_url'], $second['image_url']);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($task->fresh()->image_path);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'action' => 'image_updated']);
    }

    public function test_can_remove_image(): void
    {
        $data = $this->createTaskWithImage();
        $path = TaskModel::find($data['id'])->image_path;

        $this->deleteJson("/api/tasks/{$data['id']}/image")
            ->assertOk()
            ->assertJsonPath('data.image_url', null);

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('task_activities', ['task_id' => $data['id'], 'action' => 'image_removed']);
    }

    public function test_deleting_task_deletes_its_image(): void
    {
        $data = $this->createTaskWithImage();
        $path = TaskModel::find($data['id'])->image_path;

        $this->deleteJson("/api/tasks/{$data['id']}")->assertNoContent();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_rejects_non_image_and_too_large_files(): void
    {
        $this->post('/api/tasks', [
            'title' => 'File lạ',
            'image' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->post('/api/tasks', [
            'title' => 'Ảnh to',
            'image' => UploadedFile::fake()->image('to.jpg')->size(6000),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('tasks', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_other_users_cannot_view_or_change_image(): void
    {
        $data = $this->createTaskWithImage();

        $this->actingAs(UserModel::factory()->create());

        $this->get($data['image_url'])->assertNotFound();
        $this->post("/api/tasks/{$data['id']}/image", [
            'image' => UploadedFile::fake()->image('hack.png'),
        ], ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_guest_cannot_view_image(): void
    {
        $data = $this->createTaskWithImage();

        auth()->logout();

        $this->get($data['image_url'])->assertRedirect('/login');
    }
}
