<?php

namespace App\Data\Task\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Eloquent - chỉ dùng trong tầng Data, không lộ ra Domain/Presentation.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property bool $is_completed
 * @property string|null $image_path
 * @property string $priority
 * @property string $category
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon $created_at
 */
class TaskModel extends Model
{
    protected $table = 'tasks';

    protected $fillable = ['user_id', 'title', 'is_completed', 'image_path', 'priority', 'category', 'due_date'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'due_date' => 'date',
        ];
    }
}
