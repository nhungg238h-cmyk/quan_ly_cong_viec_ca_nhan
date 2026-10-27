<?php

namespace App\Presentation\Http\Requests\Concerns;

use App\Domain\Shared\ValueObjects\ImageUpload;

/**
 * Chuyển file upload của Laravel thành ImageUpload của tầng Domain.
 */
trait ResolvesImageUpload
{
    public const IMAGE_RULES = ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'];

    public function imageUpload(string $field = 'image'): ?ImageUpload
    {
        $file = $this->file($field);

        return $file ? new ImageUpload($file->getRealPath(), $file->guessExtension() ?? $file->extension()) : null;
    }
}
