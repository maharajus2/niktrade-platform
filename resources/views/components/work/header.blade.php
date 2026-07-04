@props([
    'title',
    'subtitle' => null,
    'initials' => 'N',
])

<header class="nik-work-header">
    <div>
        <h1 class="nik-work-title">{{ $title }}</h1>

        @if ($subtitle)
            <div class="nik-work-date">{{ $subtitle }}</div>
        @endif
    </div>

    <div class="nik-work-header-tools">
        <div class="nik-work-search" aria-label="Поиск">
            <span aria-hidden="true">⌕</span>
            <span>Поиск...</span>
        </div>

        <button type="button" class="nik-work-icon-button" aria-label="Уведомления">!</button>
        <div class="nik-work-avatar" aria-label="Профиль">{{ $initials }}</div>
    </div>
</header>
