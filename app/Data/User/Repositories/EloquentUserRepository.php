<?php

namespace App\Data\User\Repositories;

use App\Data\User\Models\UserModel;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function emailExists(string $email): bool
    {
        return UserModel::where('email', $email)->exists();
    }

    public function create(string $name, string $email, string $passwordHash): User
    {
        $model = new UserModel();
        $model->name = $name;
        $model->email = $email;
        // Cast 'hashed' nhận ra chuỗi đã băm nên không băm lại lần nữa.
        $model->password = $passwordHash;
        $model->save();

        return $this->toEntity($model);
    }

    public function find(int $id): ?User
    {
        $model = UserModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function save(User $user): void
    {
        $model = UserModel::findOrFail($user->id());
        $model->name = $user->name();
        $model->avatar_path = $user->avatarPath();
        $model->save();
    }

    public function passwordHash(int $id): ?string
    {
        return UserModel::whereKey($id)->value('password');
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $model = UserModel::findOrFail($id);
        $model->password = $passwordHash;
        $model->save();
    }

    private function toEntity(UserModel $model): User
    {
        return new User($model->id, $model->name, $model->email, $model->avatar_path);
    }
}
