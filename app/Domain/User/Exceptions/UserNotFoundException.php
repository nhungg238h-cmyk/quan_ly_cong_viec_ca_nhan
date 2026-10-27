<?php

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("Không tìm thấy tài khoản #{$id}.");
    }
}
