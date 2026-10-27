<?php

namespace App\Presentation\Http\Resources;

use App\Domain\Task\Entities\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Chuyển thực thể Task thành JSON trả cho client.
 *
 * @property Task $resource
 */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $task = $this->resource;

        return [
            'id' => $task->id(),
            'title' => $task->title(),
            'completed' => $task->isCompleted(),
            'priority' => $task->priority()->value,
            'category' => $task->category()->value,
            'due_date' => $task->dueDate()?->format('Y-m-d'),
            // Tham số v đổi theo file ảnh để trình duyệt không dùng ảnh cũ trong cache.
            'image_url' => $task->hasImage()
                ? route('tasks.image', ['id' => $task->id(), 'v' => substr(md5($task->imagePath()), 0, 8)])
                : null,
            'created_at' => $task->createdAt()->format(DATE_ATOM),
        ];
    }
}
