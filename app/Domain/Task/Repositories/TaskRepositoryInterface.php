<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\Entities\Task;

/**
 * Hợp đồng lưu trữ do tầng Domain định nghĩa; tầng Data hiện thực.
 */
interface TaskRepositoryInterface
{
    /**
     * @return Task[] Công việc của người dùng, mới nhất trước.
     */
    public function allForUser(int $userId): array;

    /**
     * Chỉ trả về công việc nếu thuộc về người dùng này.
     */
    public function findForUser(int $id, int $userId): ?Task;

    /**
     * Lưu (thêm mới hoặc cập nhật) và trả về thực thể đã có id.
     */
    public function save(Task $task): Task;

    public function delete(int $id): void;
}
