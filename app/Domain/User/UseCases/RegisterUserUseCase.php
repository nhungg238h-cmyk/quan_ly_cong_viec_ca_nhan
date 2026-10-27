<?php

namespace App\Domain\User\UseCases;

use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Services\PasswordHasherInterface;

/**
 * Tạo tài khoản mới.
 */
final class RegisterUserUseCase
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
    ) {
    }

    public function execute(string $name, string $email, string $password): User
    {
        $name = User::normalizeName($name);
        $email = mb_strtolower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidUserException('Email không hợp lệ.');
        }

        self::assertPasswordIsStrongEnough($password);

        if ($this->users->emailExists($email)) {
            throw new InvalidUserException('Email này đã được đăng ký.');
        }

        return $this->users->create($name, $email, $this->hasher->hash($password));
    }

    public static function assertPasswordIsStrongEnough(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidUserException('Mật khẩu phải có ít nhất '.self::MIN_PASSWORD_LENGTH.' ký tự.');
        }
    }
}
