<?php

namespace App\Domain\User\UseCases;

use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Services\PasswordHasherInterface;

final class ChangePasswordUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
    ) {
    }

    public function execute(int $userId, string $currentPassword, string $newPassword): void
    {
        $hash = $this->users->passwordHash($userId) ?? throw UserNotFoundException::withId($userId);

        if (! $this->hasher->check($currentPassword, $hash)) {
            throw new InvalidUserException('Mật khẩu hiện tại không đúng.');
        }

        RegisterUserUseCase::assertPasswordIsStrongEnough($newPassword);

        $this->users->updatePassword($userId, $this->hasher->hash($newPassword));
    }
}
