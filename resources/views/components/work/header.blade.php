@props([
    'title',
    'subtitle' => null,
    'initials' => 'N',
    'user' => null,
    'actions' => null,
    'greetingName' => null,
])

<header class="nik-work-header">
    <div>
        <h1
            class="nik-work-title"
            @if (filled($greetingName))
                data-work-greeting-title
                data-work-greeting-name="{{ $greetingName }}"
            @endif
        >
            {{ $title }}
        </h1>

        @if ($subtitle)
            <div class="nik-work-date">{{ $subtitle }}</div>
        @endif
    </div>

    <div class="nik-work-header-tools">
        <div class="nik-work-search" aria-label="Поиск">
            <x-work.icon name="search" />
            <span>Поиск...</span>
        </div>

        @if ($actions)
            <div class="nik-work-header-actions">
                {{ $actions }}
            </div>
        @endif

        <span class="nik-work-toolbar-divider" aria-hidden="true"></span>

        <button type="button" class="nik-work-icon-button has-badge" aria-label="Уведомления">
            <x-work.icon name="bell" />
            <span class="nik-work-notification-count">3</span>
        </button>

        <x-work.user-menu :user="$user" :initials="$initials" :show-chevron="false" />
    </div>
</header>
