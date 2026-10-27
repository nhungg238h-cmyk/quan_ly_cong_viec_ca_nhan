<?php

namespace App\Data\Shared\Services;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Shared\ValueObjects\ImageUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Lưu ảnh trên disk "local" (storage/app/private) - không truy cập trực tiếp qua URL,
 * chỉ trả về qua route có kiểm tra quyền.
 */
final class LocalImageStorage implements ImageStorageInterface
{
    public function store(ImageUpload $image, string $folder): string
    {
        $path = trim($folder, '/').'/'.Str::uuid().'.'.strtolower($image->extension);

        Storage::disk('local')->put($path, fopen($image->sourcePath, 'r'));

        return $path;
    }

    public function delete(string $path): void
    {
        Storage::disk('local')->delete($path);
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }
}
