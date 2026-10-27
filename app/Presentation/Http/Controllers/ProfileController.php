<?php

namespace App\Presentation\Http\Controllers;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Task\UseCases\GetTasksUseCase;
use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\UseCases\ChangePasswordUseCase;
use App\Domain\User\UseCases\GetUserUseCase;
use App\Domain\User\UseCases\UpdateProfileUseCase;
use App\Presentation\Http\Concerns\ServesPrivateImages;
use App\Presentation\Http\Requests\ChangePasswordRequest;
use App\Presentation\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Trang hồ sơ: ảnh đại diện, họ tên, đổi mật khẩu.
 */
class ProfileController
{
    use ServesPrivateImages;

    public function show(Request $request, GetUserUseCase $getUser, GetTasksUseCase $getTasks): View
    {
        $tasks = $getTasks->execute($request->user()->id);
        $completed = count(array_filter($tasks, fn ($task) => $task->isCompleted()));

        return view('profile.show', [
            'user' => $getUser->execute($request->user()->id),
            'memberSince' => $request->user()->created_at,
            'stats' => [
                ['label' => 'Công việc', 'value' => count($tasks)],
                ['label' => 'Đã hoàn thành', 'value' => $completed],
                ['label' => 'Tỉ lệ hoàn thành', 'value' => $tasks ? round($completed / count($tasks) * 100).'%' : '0%'],
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileUseCase $updateProfile): RedirectResponse
    {
        $updateProfile->execute(
            $request->user()->id,
            $request->validated('name'),
            $request->imageUpload('avatar'),
            $request->boolean('remove_avatar'),
        );

        return redirect()->route('profile')->with('status', 'Đã cập nhật hồ sơ.');
    }

    public function updatePassword(ChangePasswordRequest $request, ChangePasswordUseCase $changePassword): RedirectResponse
    {
        try {
            $changePassword->execute(
                $request->user()->id,
                $request->validated('current_password'),
                $request->validated('password'),
            );
        } catch (InvalidUserException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()], 'password');
        }

        return redirect()->route('profile')->with('status', 'Đã đổi mật khẩu.');
    }

    public function avatar(Request $request, GetUserUseCase $getUser, ImageStorageInterface $images): BinaryFileResponse
    {
        $user = $getUser->execute($request->user()->id);

        return $this->privateImage($request, $user->hasAvatar() ? $images->absolutePath($user->avatarPath()) : null);
    }
}
