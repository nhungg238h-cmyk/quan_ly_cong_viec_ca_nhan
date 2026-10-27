<?php

namespace App\Domain\Shared\Services;

use App\Domain\Shared\ValueObjects\ImageUpload;

/**
 * Nơi lưu file ảnh (ảnh công việc, ảnh đại diện); tầng Data quyết định lưu ở đâu.
 */
interface ImageStorageInterface
{
    /**
     * Lưu ảnh vào thư mục và trả về đường dẫn nội bộ.
     */
    public function store(ImageUpload $image, string $folder): string;

    public function delete(string $path): void;

    /**
     * Đường dẫn tuyệt đối trên đĩa, dùng để trả file về trình duyệt.
     */
    public function absolutePath(string $path): string;
}
