@extends('layouts.app')

@section('title', 'Hồ sơ')

@section('content')
    <section class="profile-hero">
        @include('partials.avatar', ['user' => $currentUser, 'size' => 'lg'])
        <div>
            <h1 class="profile-hero__name">{{ $user->name() }}</h1>
            <p class="profile-hero__email">{{ $user->email() }}</p>
            <p class="profile-hero__since">Thành viên từ {{ $memberSince->format('d/m/Y') }}</p>
        </div>
    </section>

    <dl class="stats stats--3">
        @foreach ($stats as $stat)
            <div class="stats__item">
                <dt>{{ $stat['label'] }}</dt>
                <dd>{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="profile-grid">
        <section class="panel" aria-labelledby="info-title">
            <h2 class="panel__title" id="info-title">@include('partials.icon', ['name' => 'user']) Thông tin cá nhân</h2>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="auth-form" novalidate>
                @csrf
                <div class="field">
                    <span class="field__label">Ảnh đại diện</span>
                    <div class="avatar-picker">
                        <span class="avatar avatar--md" id="avatar-preview" aria-hidden="true">
                            @if ($currentUser['avatar_url'])
                                <img src="{{ $currentUser['avatar_url'] }}" alt="">
                            @else
                                {{ $currentUser['initial'] }}
                            @endif
                        </span>
                        <div class="avatar-picker__actions">
                            <label class="btn btn--ghost btn--sm" for="avatar">@include('partials.icon', ['name' => 'camera']) Chọn ảnh</label>
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="visually-hidden">
                            @if ($user->hasAvatar())
                                <label class="checkbox"><input type="checkbox" name="remove_avatar" value="1"> Gỡ ảnh hiện tại</label>
                            @endif
                        </div>
                    </div>
                    @error('avatar') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="name">Họ tên</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name()) }}" maxlength="100" required>
                    @error('name') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="email-readonly">Email</label>
                    <input type="email" id="email-readonly" value="{{ $user->email() }}" disabled>
                </div>
                <button type="submit" class="btn btn--primary">Lưu hồ sơ</button>
            </form>
        </section>

        <section class="panel" aria-labelledby="password-title">
            <h2 class="panel__title" id="password-title">@include('partials.icon', ['name' => 'lock']) Đổi mật khẩu</h2>
            <form method="POST" action="{{ route('profile.password') }}" class="auth-form" novalidate>
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    @error('current_password', 'password') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="new_password">Mật khẩu mới <span class="field__hint">(ít nhất 8 ký tự)</span></label>
                    <input type="password" id="new_password" name="password" autocomplete="new-password" minlength="8" required>
                    @error('password', 'password') <p class="field__error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Nhập lại mật khẩu mới</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn--primary">Đổi mật khẩu</button>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('avatar').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = '';
            document.getElementById('avatar-preview').replaceChildren(img);
        });
    </script>
@endpush
