@extends('layouts.guest')

@section('title', 'Tạo tài khoản')

@section('content')
    <h1 class="auth-card__title">Tạo tài khoản</h1>
    <p class="auth-card__subtitle">Mỗi tài khoản có danh sách công việc và lịch sử riêng.</p>

    <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
        @csrf
        <div class="field">
            <label for="name">Họ tên</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" required autofocus>
            @error('name') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password">Mật khẩu <span class="field__hint">(ít nhất 8 ký tự)</span></label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
            @error('password') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Nhập lại mật khẩu</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block">Tạo tài khoản</button>
    </form>

    <p class="auth-switch">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
@endsection
