<?php

namespace App\Domain\Task\Exceptions;

use RuntimeException;

final class TaskNotFoundException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("Không tìm thấy công việc #{$id}.");
    }
}
