<?php

namespace Tests\Support;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, array{user: User, hash: string}> */
    public array $users = [];

    public function emailExists(string $email): bool
    {
        foreach ($this->users as $row) {
            if ($row['user']->email() === $email) {
                return true;
            }
        }

        return false;
    }

    public function create(string $name, string $email, string $passwordHash): User
    {
        $id = count($this->users) + 1;
        $this->users[$id] = ['user' => new User($id, $name, $email), 'hash' => $passwordHash];

        return clone $this->users[$id]['user'];
    }

    public function find(int $id): ?User
    {
        return isset($this->users[$id]) ? clone $this->users[$id]['user'] : null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id()]['user'] = clone $user;
    }

    public function passwordHash(int $id): ?string
    {
        return $this->users[$id]['hash'] ?? null;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->users[$id]['hash'] = $passwordHash;
    }
}
