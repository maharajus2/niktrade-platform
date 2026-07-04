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
            <x-work.icon name="search" />
            <span>Поиск...</span>
        </div>

        <button type="button" class="nik-work-icon-button has-badge" aria-label="Уведомления">
            <x-work.icon name="bell" />
            <span class="nik-work-notification-count">3</span>
        </button>
        <button type="button" class="nik-work-icon-button" aria-label="Выйти">
            <x-work.icon name="log-out" />
        </button>
    </div>
</header>
