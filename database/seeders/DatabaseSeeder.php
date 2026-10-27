<?php

namespace Database\Seeders;

use App\Data\Task\Models\TaskActivityModel;
use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\Task\UseCases\CreateTaskUseCase;
use App\Domain\Task\UseCases\SetTaskCompletionUseCase;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\User\UseCases\RegisterUserUseCase;
use DateTimeImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Tạo tài khoản dùng thử (config/demo.php) qua chính các use case của ứng dụng,
     * rồi lùi thời gian lịch sử về các ngày trước để biểu đồ 7 ngày có dữ liệu.
     */
    public function run(
        RegisterUserUseCase $register,
        CreateTaskUseCase $createTask,
        SetTaskCompletionUseCase $setCompletion,
    ): void {
        foreach (config('demo.accounts') as $account) {
            $user = $register->execute($account['name'], $account['email'], $account['password']);

            foreach ($account['tasks'] as $data) {
                $imagePath = isset($data['image']) ? SampleImages::make($data['image']) : null;

                $task = $createTask->execute($user->id(), new TaskDetails(
                    $data['title'],
                    TaskPriority::from($data['priority']),
                    TaskCategory::from($data['category']),
                    $data['due'] === null ? null : new DateTimeImmutable("{$data['due']} days"),
                ), $imagePath ? new ImageUpload($imagePath, 'png') : null);

                $daysAgo = $data['done'] ?? 0;
                $this->backdateLastActivity($task->id(), $daysAgo + 1);

                if ($data['done'] !== null) {
                    $setCompletion->execute($user->id(), $task->id(), true);
                    $this->backdateLastActivity($task->id(), $daysAgo);
                }

                if ($imagePath) {
                    @unlink($imagePath);
                }
            }
        }
    }

    private function backdateLastActivity(int $taskId, int $daysAgo): void
    {
        if ($daysAgo <= 0) {
            return;
        }

        TaskActivityModel::where('task_id', $taskId)->latest('id')->first()
            ?->update(['created_at' => now()->subDays($daysAgo)->setTime(rand(8, 20), rand(0, 59))]);
    }
}
