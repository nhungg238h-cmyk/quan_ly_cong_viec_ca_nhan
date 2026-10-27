<?php

namespace App\Data\Task\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $task_id
 * @property string $task_title
 * @property string $action
 * @property \Illuminate\Support\Carbon $created_at
 */
class TaskActivityModel extends Model
{
    protected $table = 'task_activities';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'task_id', 'task_title', 'action', 'created_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
