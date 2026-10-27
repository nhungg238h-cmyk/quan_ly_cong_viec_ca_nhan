<?php

namespace App\Domain\User\UseCases;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Shared\ValueObjects\ImageUpload;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepositoryInterface;

/**
 * Đổi tên và ảnh đại diện. $avatar: ảnh mới; $removeAvatar: gỡ ảnh hiện tại.
 */
final class UpdateProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ImageStorageInterface $images,
    ) {
    }

    public function execute(int $userId, string $name, ?ImageUpload $avatar = null, bool $removeAvatar = false): User
    {
        $user = $this->users->find($userId) ?? throw UserNotFoundException::withId($userId);

        $user->rename($name);

        $previous = null;
        if ($avatar !== null) {
            $previous = $user->replaceAvatar($this->images->store($avatar, User::AVATAR_FOLDER));
        } elseif ($removeAvatar) {
            $previous = $user->replaceAvatar(null);
        }

        $this->users->save($user);

        if ($previous !== null) {
            $this->images->delete($previous);
        }

        return $user;
    }
}
