<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController
{
    public function create(): View
    {
        return view('auth.login', [
            'demoAccounts' => config('demo.show_on_login') ? config('demo.accounts') : [],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => mb_strtolower(trim($request->validated('email'))),
            'password' => $request->validated('password'),
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tasks.page'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
