<?php

namespace App\Presentation\Http\Requests\Concerns;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\ValueObjects\TaskCategory;
use App\Domain\Task\ValueObjects\TaskDetails;
use App\Domain\Task\ValueObjects\TaskPriority;
use DateTimeImmutable;
use Illuminate\Validation\Rule;

/**
 * Luật kiểm tra + chuyển dữ liệu form thành TaskDetails (dùng cho thêm và sửa).
 */
trait BuildsTaskDetails
{
    protected function taskDetailRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.Task::MAX_TITLE_LENGTH],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'category' => ['nullable', Rule::enum(TaskCategory::class)],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function taskDetailMessages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tên công việc.',
            'title.max' => 'Tên công việc không được dài quá :max ký tự.',
            'priority.enum' => 'Mức ưu tiên không hợp lệ.',
            'category.enum' => 'Danh mục không hợp lệ.',
            'due_date.date_format' => 'Hạn chót không hợp lệ.',
        ];
    }

    public function taskDetails(): TaskDetails
    {
        $dueDate = $this->validated('due_date');

        return new TaskDetails(
            $this->validated('title'),
            TaskPriority::tryFrom((string) $this->validated('priority')) ?? TaskPriority::Medium,
            TaskCategory::tryFrom((string) $this->validated('category')) ?? TaskCategory::Other,
            $dueDate ? DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate) : null,
        );
    }
}
