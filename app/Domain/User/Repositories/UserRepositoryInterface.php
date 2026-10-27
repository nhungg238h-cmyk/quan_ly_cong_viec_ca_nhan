<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\User;

interface UserRepositoryInterface
{
    public function emailExists(string $email): bool;

    public function create(string $name, string $email, string $passwordHash): User;

    public function find(int $id): ?User;

    /**
     * Lưu tên và ảnh đại diện.
     */
    public function save(User $user): void;

    public function passwordHash(int $id): ?string;

    public function updatePassword(int $id, string $passwordHash): void;
}
