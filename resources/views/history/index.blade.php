@extends('layouts.app')

@section('title', 'Lịch sử')

@section('content')
    <header class="page-head">
        <h1 class="page-head__title">Lịch sử hoạt động</h1>
        <p class="page-head__subtitle">Mọi thao tác với công việc của bạn được lưu lại tại đây.</p>
    </header>

    <dl class="stats">
        @foreach ($stats as $stat)
            <div class="stats__item">
                <dt>{{ $stat['label'] }}</dt>
                <dd>{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    <section class="panel trend" aria-labelledby="trend-title">
        <div class="panel__head">
            <h2 class="panel__title" id="trend-title">Hoàn thành 7 ngày qua</h2>
            <span class="trend__total">{{ $trendTotal }} lượt</span>
        </div>
        <ol class="trend__bars">
            @foreach ($trend as $day)
                <li @class(['trend__day', 'is-today' => $day['is_today']]) title="{{ $day['date'] }}: {{ $day['count'] }} lượt hoàn thành">
                    <span class="trend__count">{{ $day['count'] }}</span>
                    <span class="trend__track"><span class="trend__bar" style="height: {{ max($day['percent'], 3) }}%"></span></span>
                    <span class="trend__label">{{ $day['label'] }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    @forelse ($groups as $day => $items)
        <section class="history">
            <h2 class="history__day">{{ $day }}</h2>
            <ol class="history__list">
                @foreach ($items as $item)
                    <li class="history__item history__item--{{ $item['action'] }}">
                        <span class="history__icon">@include('partials.icon', ['name' => $item['icon']])</span>
                        <p class="history__text">{{ $item['label'] }} <strong>{{ $item['title'] }}</strong></p>
                        <time class="history__time" datetime="{{ $item['datetime'] }}">{{ $item['time'] }}</time>
                    </li>
                @endforeach
            </ol>
        </section>
    @empty
        <div class="empty">
            <span class="empty__icon">@include('partials.icon', ['name' => 'clock'])</span>
            <p class="empty__title">Chưa có hoạt động nào</p>
            <p class="empty__hint">Thêm hoặc cập nhật công việc để lịch sử xuất hiện ở đây.</p>
        </div>
    @endforelse

    @if ($total >= $limit)
        <p class="history__more">Đang hiển thị {{ $limit }} hoạt động gần nhất.</p>
    @endif
@endsection
