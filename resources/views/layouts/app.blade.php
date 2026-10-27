@extends('layouts.base')

@section('body')
    <main class="app">
        <nav class="topbar" aria-label="Điều hướng chính">
            <a href="{{ route('tasks.page') }}" class="brand" aria-label="Trang chủ">
                @include('partials.logo')
            </a>
            <div class="topbar__links">
                <a href="{{ route('tasks.page') }}" @class(['topbar__link', 'is-active' => request()->routeIs('tasks.page')])>Công việc</a>
                <a href="{{ route('history') }}" @class(['topbar__link', 'is-active' => request()->routeIs('history')])>Lịch sử</a>
            </div>
            <div class="topbar__user">
                <span class="topbar__me" title="{{ $currentUser['email'] }}">
                    @include('partials.avatar', ['user' => $currentUser, 'size' => 'sm'])
                    <span class="topbar__name">{{ $currentUser['name'] }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--sm topbar__logout" title="Đăng xuất">
                        @include('partials.icon', ['name' => 'logout'])
                        <span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </nav>

        @if (session('status'))
            <p class="notice" role="status">@include('partials.icon', ['name' => 'check']) {{ session('status') }}</p>
        @endif

        @yield('content')
    </main>

    <div class="toasts" id="toasts" aria-live="polite"></div>
@endsection
