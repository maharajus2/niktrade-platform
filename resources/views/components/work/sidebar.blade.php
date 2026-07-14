@props([
    'active' => 'workplace',
    'user' => null,
])

@php
    $user ??= auth()->user();
    $groups = \App\Support\WorkNavigation::groups($active, $user);
@endphp

<aside class="nik-work-sidebar" aria-label="Рабочая навигация">
    <a href="{{ \App\Filament\Pages\Workplace::getUrl() }}" class="nik-work-logo">
        <img class="nik-work-brand-logo" src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
    </a>

    <nav class="nik-work-nav">
        @foreach ($groups as $group => $items)
            <div class="nik-work-nav-section">{{ $group }}</div>

            @foreach ($items as $item)
                <a href="{{ $item['url'] }}" class="nik-work-nav-link {{ ($item['active'] ?? false) ? 'is-active' : '' }}" title="{{ $item['label'] }}">
                    <span class="nik-work-nav-icon"><x-work.icon :name="$item['icon']" /></span>
                    <span class="nik-work-nav-label">{{ $item['label'] }}</span>
                    @if (isset($item['badge']))
                        <span class="nik-work-nav-badge">{{ $item['badge'] }}</span>
                    @else
                        <span class="nik-work-nav-trailing"></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <button type="button" class="nik-work-sidebar-toggle" aria-label="Свернуть боковую панель" aria-expanded="true" data-sidebar-toggle>
        <span class="nik-work-sidebar-toggle-icon nik-work-sidebar-toggle-icon--collapse">
            <x-work.icon name="chevron-left" />
        </span>
        <span class="nik-work-sidebar-toggle-icon nik-work-sidebar-toggle-icon--expand">
            <x-work.icon name="chevron-right" />
        </span>
        <span class="nik-work-sidebar-toggle-label">Свернуть меню</span>
    </button>
</aside>
