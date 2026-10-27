<?php

namespace Tests\Feature;

use App\Data\Task\Models\TaskActivityModel;
use App\Data\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_every_action_newest_first(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $id = $this->postJson('/api/tasks', ['title' => 'Nộp báo cáo'])->json('data.id');
        $this->putJson("/api/tasks/{$id}", ['title' => 'Nộp báo cáo tuần', 'priority' => 'high']);
        $this->patchJson("/api/tasks/{$id}", ['completed' => true]);
        $this->patchJson("/api/tasks/{$id}", ['completed' => false]);
        $this->deleteJson("/api/tasks/{$id}");

        $this->get('/history')
            ->assertOk()
            ->assertSee('Lịch sử hoạt động')
            ->assertSee('Hôm nay')
            ->assertSeeInOrder([
                'Đã xóa công việc',
                'Đánh dấu chưa hoàn thành',
                'Đã hoàn thành',
                'Đã sửa công việc',
                'Đã thêm công việc',
            ])
            ->assertViewHas('stats', fn (array $stats) => array_column($stats, 'value') === [1, 1, 1, 1]);
    }

    public function test_history_shows_seven_day_completion_chart(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        $id = $this->postJson('/api/tasks', ['title' => 'Việc'])->json('data.id');
        $this->patchJson("/api/tasks/{$id}", ['completed' => true]);
        TaskActivityModel::create([
            'user_id' => $user->id, 'task_id' => $id, 'task_title' => 'Việc', 'action' => 'completed',
            'created_at' => now()->subDays(3),
        ]);
        TaskActivityModel::create([
            'user_id' => $user->id, 'task_id' => $id, 'task_title' => 'Việc cũ', 'action' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        $this->get('/history')
            ->assertOk()
            ->assertSee('Hoàn thành 7 ngày qua')
            ->assertViewHas('trendTotal', 2)
            ->assertViewHas('trend', function (array $trend) {
                return count($trend) === 7
                    && $trend[6]['is_today'] && $trend[6]['count'] === 1
                    && $trend[3]['count'] === 1;
            });
    }

    public function test_history_is_private_to_each_user(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $this->postJson('/api/tasks', ['title' => 'Việc bí mật của A']);

        $this->actingAs(UserModel::factory()->create());

        $this->get('/history')
            ->assertOk()
            ->assertDontSee('Việc bí mật của A')
            ->assertSee('Chưa có hoạt động nào');
    }
}
