@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
    <h1 class="auth-card__title">Đăng nhập</h1>
    <p class="auth-card__subtitle">Chào mừng trở lại! Đăng nhập để xem công việc của bạn.</p>

    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            @error('password') <p class="field__error">{{ $message }}</p> @enderror
        </div>
        <label class="checkbox">
            <input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập
        </label>
        <button type="submit" class="btn btn--primary btn--block">Đăng nhập</button>
    </form>

    <p class="auth-switch">Chưa có tài khoản? <a href="{{ route('register') }}">Tạo tài khoản mới</a></p>

    @if ($demoAccounts)
        <section class="demo" aria-labelledby="demo-title">
            <h2 class="demo__title" id="demo-title">Tài khoản dùng thử</h2>
            <p class="demo__hint">Bấm vào một tài khoản để điền sẵn thông tin.</p>
            @foreach ($demoAccounts as $account)
                <button type="button" class="demo__account" data-email="{{ $account['email'] }}" data-password="{{ $account['password'] }}">
                    <span class="avatar avatar--sm demo__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($account['name'], 0, 1)) }}</span>
                    <span>
                        <span class="demo__name">{{ $account['name'] }}</span>
                        <span class="demo__cred">{{ $account['email'] }} · {{ $account['password'] }}</span>
                    </span>
                </button>
            @endforeach
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.demo__account').forEach((btn) => btn.addEventListener('click', () => {
            document.getElementById('email').value = btn.dataset.email;
            document.getElementById('password').value = btn.dataset.password;
            document.getElementById('password').focus();
        }));
    </script>
@endpush
