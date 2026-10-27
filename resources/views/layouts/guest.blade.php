@extends('layouts.base')

@section('body')
    <main class="auth-shell">
        <aside class="auth-side">
            <div class="brand">
                @include('partials.logo')
                <span class="brand__name">Quản lý công việc</span>
            </div>
            <h2 class="auth-side__title">Sắp xếp công việc mỗi ngày gọn gàng hơn.</h2>
            <ul class="auth-side__list">
                <li>@include('partials.icon', ['name' => 'layers']) Phân loại theo danh mục và mức ưu tiên</li>
                <li>@include('partials.icon', ['name' => 'calendar']) Đặt hạn chót, theo dõi việc quá hạn</li>
                <li>@include('partials.icon', ['name' => 'image']) Đính kèm hình ảnh cho từng việc</li>
                <li>@include('partials.icon', ['name' => 'chart']) Xem lại lịch sử và tiến độ trong tuần</li>
            </ul>
        </aside>

        <section class="auth-card">
            <div class="brand auth-card__brand">
                @include('partials.logo')
                <span class="brand__name">Quản lý công việc</span>
            </div>
            @yield('content')
        </section>
    </main>
@endsection
