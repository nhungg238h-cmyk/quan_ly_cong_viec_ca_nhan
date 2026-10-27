<?php

namespace App\Providers;

use App\Data\Shared\Services\LocalImageStorage;
use App\Data\Task\Repositories\EloquentTaskActivityRepository;
use App\Data\Task\Repositories\EloquentTaskRepository;
use App\Data\User\Repositories\EloquentUserRepository;
use App\Data\User\Services\LaravelPasswordHasher;
use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Task\Repositories\TaskActivityRepositoryInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Services\PasswordHasherInterface;
use App\Presentation\Http\ViewComposers\CurrentUserComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Nối tầng Domain với tầng Data (Dependency Inversion).
 */
class TaskServiceProvider extends ServiceProvider
{
    public array $bindings = [
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
        TaskActivityRepositoryInterface::class => EloquentTaskActivityRepository::class,
        ImageStorageInterface::class => LocalImageStorage::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        PasswordHasherInterface::class => LaravelPasswordHasher::class,
    ];

    public function boot(): void
    {
        // Các trang sau đăng nhập (và layout của chúng) đều cần biết người dùng hiện tại.
        View::composer(['layouts.app', 'tasks.*', 'history.*', 'profile.*'], CurrentUserComposer::class);
    }
}
