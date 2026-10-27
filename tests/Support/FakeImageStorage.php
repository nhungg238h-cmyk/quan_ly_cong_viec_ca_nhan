<?php

namespace Tests\Support;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Shared\ValueObjects\ImageUpload;

final class FakeImageStorage implements ImageStorageInterface
{
    /** @var array<string, true> */
    public array $stored = [];

    private int $counter = 0;

    public function store(ImageUpload $image, string $folder): string
    {
        $path = $folder.'/'.++$this->counter.'.'.$image->extension;
        $this->stored[$path] = true;

        return $path;
    }

    public function delete(string $path): void
    {
        unset($this->stored[$path]);
    }

    public function absolutePath(string $path): string
    {
        return '/fake/'.$path;
    }
}
