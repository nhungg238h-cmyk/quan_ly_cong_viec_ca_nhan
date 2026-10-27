<?php

namespace App\Domain\User\Entities;

use App\Domain\User\Exceptions\InvalidUserException;

final class User
{
    public const MAX_NAME_LENGTH = 100;

    public const AVATAR_FOLDER = 'avatars';

    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private ?string $avatarPath = null,
    ) {
    }

    public static function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidUserException('Vui lòng nhập họ tên.');
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidUserException('Họ tên không được dài quá '.self::MAX_NAME_LENGTH.' ký tự.');
        }

        return $name;
    }

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    /**
     * Đổi ảnh đại diện (null để gỡ), trả về đường dẫn ảnh cũ để dọn dẹp.
     */
    public function replaceAvatar(?string $avatarPath): ?string
    {
        $previous = $this->avatarPath;
        $this->avatarPath = $avatarPath;

        return $previous;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function avatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function hasAvatar(): bool
    {
        return $this->avatarPath !== null;
    }
}
