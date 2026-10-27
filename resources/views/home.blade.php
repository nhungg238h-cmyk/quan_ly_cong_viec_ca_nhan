@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')
    <header class="page-head">
        <h1 class="page-head__title">Xin chào, {{ $currentUser['name'] }}</h1>
        <p class="page-head__subtitle">Bạn đã đăng nhập. Danh sách công việc sẽ có ở bản cập nhật tiếp theo.</p>
    </header>
@endsection
