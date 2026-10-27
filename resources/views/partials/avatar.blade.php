{{-- $user: ['name', 'initial', 'avatar_url'], $size: sm | lg --}}
<span class="avatar avatar--{{ $size ?? 'sm' }}" aria-hidden="true">
    @if ($user['avatar_url'])
        <img src="{{ $user['avatar_url'] }}" alt="">
    @else
        {{ $user['initial'] }}
    @endif
</span>
