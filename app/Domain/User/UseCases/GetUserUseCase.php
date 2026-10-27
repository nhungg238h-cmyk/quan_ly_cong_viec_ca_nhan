<?php

namespace App\Domain\User\UseCases;

use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepositoryInterface;

final class GetUserUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function execute(int $id): User
    {
        return $this->users->find($id) ?? throw UserNotFoundException::withId($id);
    }
}
