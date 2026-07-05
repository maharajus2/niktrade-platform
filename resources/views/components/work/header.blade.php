@props([
    'title',
    'subtitle' => null,
    'initials' => 'N',
    'user' => null,
])

@php
    $avatarUrl = $user?->avatar_path ? asset('storage/'.$user->avatar_path) : null;
@endphp

<header class="nik-work-header">
    <div>
        <h1 class="nik-work-title">{{ $title }}</h1>

        @if ($subtitle)
            <div class="nik-work-date">{{ $subtitle }}</div>
        @endif
    </div>

    <div class="nik-work-header-tools">
        <div class="nik-work-search" aria-label="Поиск">
            <x-work.icon name="search" />
            <span>Поиск...</span>
            <kbd>⌘K</kbd>
        </div>

        <span class="nik-work-toolbar-divider" aria-hidden="true"></span>

        <button type="button" class="nik-work-icon-button has-badge" aria-label="Уведомления">
            <x-work.icon name="bell" />
            <span class="nik-work-notification-count">3</span>
        </button>
        <div class="nik-work-user-pill" aria-label="Профиль">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="" />
            @else
                <span>{{ $initials }}</span>
            @endif
            <span class="nik-work-user-chevron">⌄</span>
        </div>
    </div>
</header>
