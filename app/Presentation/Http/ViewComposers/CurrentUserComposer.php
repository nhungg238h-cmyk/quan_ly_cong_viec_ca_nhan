<?php

namespace App\Presentation\Http\ViewComposers;

use App\Domain\User\UseCases\GetUserUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cung cấp thông tin người dùng đang đăng nhập (tên, ảnh đại diện) cho layout.
 */
class CurrentUserComposer
{
    public function __construct(
        private Request $request,
        private GetUserUseCase $getUser,
    ) {
    }

    public function compose(View $view): void
    {
        $user = $this->getUser->execute($this->request->user()->id);

        $view->with('currentUser', [
            'name' => $user->name(),
            'email' => $user->email(),
            'initial' => mb_strtoupper(mb_substr($user->name(), 0, 1)),
            'avatar_url' => $user->hasAvatar()
                ? route('profile.avatar', ['v' => substr(md5($user->avatarPath()), 0, 8)])
                : null,
        ]);
    }
}
