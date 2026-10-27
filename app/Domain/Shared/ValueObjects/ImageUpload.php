<?php

namespace App\Domain\Shared\ValueObjects;

/**
 * Ảnh người dùng tải lên, mô tả độc lập với framework (file tạm + phần mở rộng).
 */
final class ImageUpload
{
    public function __construct(
        public readonly string $sourcePath,
        public readonly string $extension,
    ) {
    }
}
