@extends('layouts.app')

@section('title', 'Công việc')

@push('meta')
    <meta name="tasks-api" content="{{ route('tasks.index') }}">
@endpush

@section('content')
    <header class="page-head">
        <p class="page-head__eyebrow" id="today"></p>
        <h1 class="page-head__title"><span id="greeting">Xin chào</span>, {{ $currentUser['name'] }}</h1>
        <p class="page-head__subtitle" id="hero-summary"></p>
        <div class="progress" aria-live="polite">
            <div class="progress__bar"><span class="progress__fill" id="progress-fill"></span></div>
            <span class="progress__label" id="progress-label"></span>
        </div>
    </header>

    <form class="composer" id="task-form" autocomplete="off" novalidate>
        <div class="composer__row">
            <div class="composer__field">
                <label for="task-title" class="visually-hidden">Tên công việc</label>
                <input type="text" id="task-title" name="title" maxlength="255" placeholder="Bạn cần làm gì hôm nay?" required>
                <label class="icon-btn composer__attach" for="new-task-image" title="Đính kèm ảnh">
                    @include('partials.icon', ['name' => 'camera'])
                    <span class="visually-hidden">Đính kèm ảnh</span>
                </label>
                <input type="file" id="new-task-image" accept="image/jpeg,image/png,image/gif,image/webp" class="visually-hidden">
            </div>
            <button type="submit" class="btn btn--primary" id="task-submit">
                @include('partials.icon', ['name' => 'plus'])
                <span>Thêm</span>
            </button>
        </div>
        <div class="composer__options">
            <label class="select" title="Danh mục">
                @include('partials.icon', ['name' => 'tag'])
                <span class="visually-hidden">Danh mục</span>
                <select name="category" id="new-task-category">
                    @foreach ($options['categories'] as $value => $category)
                        <option value="{{ $value }}" @selected($value === 'other')>{{ $category['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="select" title="Mức ưu tiên">
                @include('partials.icon', ['name' => 'flag'])
                <span class="visually-hidden">Mức ưu tiên</span>
                <select name="priority" id="new-task-priority">
                    @foreach ($options['priorities'] as $value => $priority)
                        <option value="{{ $value }}" @selected($value === 'medium')>{{ $priority['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="select select--date" title="Hạn chót">
                @include('partials.icon', ['name' => 'calendar'])
                <span class="visually-hidden">Hạn chót</span>
                <input type="date" name="due_date" id="new-task-due">
            </label>
            <button type="button" class="link-btn" data-quick-due="0">Hôm nay</button>
            <button type="button" class="link-btn" data-quick-due="1">Ngày mai</button>
        </div>
        <div class="attach-preview" id="attach-preview" hidden>
            <img alt="" id="attach-preview-img">
            <span class="attach-preview__name" id="attach-preview-name"></span>
            <button type="button" class="icon-btn" id="attach-preview-remove" title="Bỏ ảnh" aria-label="Bỏ ảnh đính kèm">
                @include('partials.icon', ['name' => 'x'])
            </button>
        </div>
        <p class="form-error" id="form-error" role="alert" hidden></p>
    </form>

    <nav class="tabs" aria-label="Lọc theo trạng thái">
        @foreach (['all' => 'Tất cả', 'active' => 'Đang làm', 'today' => 'Hôm nay', 'overdue' => 'Quá hạn', 'completed' => 'Đã xong'] as $status => $label)
            <button type="button" @class(['tabs__item', 'is-active' => $status === 'all']) data-status="{{ $status }}">
                {{ $label }} <span class="tabs__count" data-count="{{ $status }}">0</span>
            </button>
        @endforeach
    </nav>

    <div class="toolbar">
        <label class="search">
            @include('partials.icon', ['name' => 'search'])
            <span class="visually-hidden">Tìm kiếm</span>
            <input type="search" id="search" placeholder="Tìm công việc…">
        </label>
        <label class="select" title="Sắp xếp">
            @include('partials.icon', ['name' => 'sort'])
            <span class="visually-hidden">Sắp xếp</span>
            <select id="sort">
                <option value="newest">Mới nhất</option>
                <option value="due">Hạn chót gần nhất</option>
                <option value="priority">Ưu tiên cao trước</option>
                <option value="title">Tên A - Z</option>
            </select>
        </label>
    </div>

    <div class="categories" role="group" aria-label="Lọc theo danh mục">
        <button type="button" class="chip is-active" data-category="all">
            @include('partials.icon', ['name' => 'layers']) Tất cả <span data-cat-count="all"></span>
        </button>
        @foreach ($options['categories'] as $value => $category)
            <button type="button" class="chip" data-category="{{ $value }}">
                @include('partials.icon', ['name' => $category['icon']]) {{ $category['label'] }} <span data-cat-count="{{ $value }}"></span>
            </button>
        @endforeach
    </div>

    <ul class="task-list" id="task-list"></ul>

    <div class="empty" id="empty-state" hidden>
        <span class="empty__icon">@include('partials.icon', ['name' => 'clipboard'])</span>
        <p class="empty__title" id="empty-title"></p>
        <p class="empty__hint" id="empty-hint"></p>
    </div>

    <footer class="list-footer" id="list-footer" hidden>
        <span id="list-footer-text"></span>
        <button type="button" class="btn btn--ghost btn--sm" id="clear-completed">
            @include('partials.icon', ['name' => 'trash']) Xóa các việc đã xong
        </button>
    </footer>

    <input type="file" id="task-image-input" accept="image/jpeg,image/png,image/gif,image/webp" class="visually-hidden" tabindex="-1" aria-hidden="true">

    <template id="task-item-template">
        <li class="task">
            <label class="task__check">
                <input type="checkbox">
                <span class="task__box" aria-hidden="true">@include('partials.icon', ['name' => 'check'])</span>
            </label>
            <button type="button" class="task__thumb" data-action="view-image" hidden>
                <img alt="" loading="lazy">
            </button>
            <div class="task__body">
                <span class="task__title"></span>
                <div class="task__meta">
                    <span class="tag task__category"></span>
                    <span class="tag task__priority"></span>
                    <span class="tag task__due" hidden></span>
                </div>
            </div>
            <div class="task__actions">
                <button type="button" class="icon-btn" data-action="edit" title="Sửa" aria-label="Sửa công việc">
                    @include('partials.icon', ['name' => 'pencil'])
                </button>
                <button type="button" class="icon-btn" data-action="set-image">
                    @include('partials.icon', ['name' => 'camera'])
                </button>
                <button type="button" class="icon-btn" data-action="remove-image" title="Gỡ ảnh" aria-label="Gỡ ảnh" hidden>
                    @include('partials.icon', ['name' => 'image-off'])
                </button>
                <button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Xóa" aria-label="Xóa công việc">
                    @include('partials.icon', ['name' => 'trash'])
                </button>
            </div>
        </li>
    </template>

    <dialog class="dialog" id="edit-dialog" aria-labelledby="edit-title">
        <form method="dialog" id="edit-form" novalidate>
            <h2 class="dialog__title" id="edit-title">Sửa công việc</h2>
            <div class="field">
                <label for="edit-task-title">Tên công việc</label>
                <input type="text" id="edit-task-title" name="title" maxlength="255" required>
            </div>
            <div class="field">
                <span class="field__label">Danh mục</span>
                <div class="choice-grid">
                    @foreach ($options['categories'] as $value => $category)
                        <label class="choice">
                            <input type="radio" name="category" value="{{ $value }}">
                            <span>@include('partials.icon', ['name' => $category['icon']]) {{ $category['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="field">
                <span class="field__label">Mức ưu tiên</span>
                <div class="choice-grid">
                    @foreach ($options['priorities'] as $value => $priority)
                        <label class="choice">
                            <input type="radio" name="priority" value="{{ $value }}">
                            <span>{{ $priority['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="field">
                <label for="edit-task-due">Hạn chót</label>
                <input type="date" id="edit-task-due" name="due_date">
            </div>
            <p class="form-error" id="edit-error" role="alert" hidden></p>
            <div class="dialog__actions">
                <button type="button" class="btn btn--ghost" id="edit-cancel">Hủy</button>
                <button type="submit" class="btn btn--primary" id="edit-save">Lưu thay đổi</button>
            </div>
        </form>
    </dialog>

    <div class="lightbox" id="lightbox" hidden>
        <button type="button" class="lightbox__close" aria-label="Đóng">@include('partials.icon', ['name' => 'x'])</button>
        <figure>
            <img alt="" id="lightbox-img">
            <figcaption id="lightbox-caption"></figcaption>
        </figure>
    </div>

    <script id="initial-tasks" type="application/json">@json($tasks)</script>
    <script id="task-options" type="application/json">@json($options)</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/tasks.js') }}?v={{ filemtime(public_path('js/tasks.js')) }}" defer></script>
@endpush
