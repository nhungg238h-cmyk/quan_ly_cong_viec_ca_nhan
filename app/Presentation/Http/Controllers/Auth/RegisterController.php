<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Exceptions\InvalidUserException;
use App\Domain\User\UseCases\RegisterUserUseCase;
use App\Presentation\Http\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterUserUseCase $register): RedirectResponse
    {
        try {
            $user = $register->execute(
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('password'),
            );
        } catch (InvalidUserException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput($request->except('password', 'password_confirmation'));
        }

        Auth::loginUsingId($user->id());
        $request->session()->regenerate();

        return redirect()->route('tasks.page')->with('status', "Chào mừng {$user->name()}! Tài khoản đã được tạo.");
    }
}
