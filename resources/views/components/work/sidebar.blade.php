@props([
    'active' => 'workplace',
    'user' => null,
])

@php
    $items = [
        ['key' => 'workplace', 'label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => \App\Filament\Pages\Workplace::getUrl()],
    ];

    $mainItems = [
        ['key' => 'orders', 'label' => 'Заказы', 'icon' => 'package', 'url' => \App\Filament\Resources\Orders\OrderResource::getUrl('index')],
        ['key' => 'calendar', 'label' => 'Календарь', 'icon' => 'calendar', 'url' => \App\Filament\Pages\MyCalendar::getUrl()],
        ['label' => 'Задачи', 'icon' => 'check-square', 'url' => '#', 'badge' => '3'],
        ['label' => 'Заявки', 'icon' => 'link', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')],
        ['label' => 'Документы', 'icon' => 'file', 'url' => '#employee-documents'],
        ['label' => 'Сообщения', 'icon' => 'message', 'url' => '#', 'badge' => '2'],
        ['label' => 'Справочники', 'icon' => 'book', 'url' => '#'],
    ];

    $initials = $user
        ? collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->join('')
        : 'N';
@endphp

<aside class="nik-work-sidebar" aria-label="Рабочая навигация">
    <a href="{{ \App\Filament\Pages\Workplace::getUrl() }}" class="nik-work-logo">
        <img class="nik-work-brand-logo" src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
    </a>

    <nav class="nik-work-nav">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}" class="nik-work-nav-link {{ $active === $item['key'] ? 'is-active' : '' }}" title="{{ $item['label'] }}">
                <span class="nik-work-nav-icon"><x-work.icon :name="$item['icon']" /></span>
                <span class="nik-work-nav-label">{{ $item['label'] }}</span>
                <span class="nik-work-nav-trailing"></span>
            </a>
        @endforeach

        <div class="nik-work-nav-section">Основное</div>

        @foreach ($mainItems as $item)
            <a href="{{ $item['url'] }}" class="nik-work-nav-link {{ ($item['key'] ?? null) === $active ? 'is-active' : '' }}" title="{{ $item['label'] }}">
                <span class="nik-work-nav-icon"><x-work.icon :name="$item['icon']" /></span>
                <span class="nik-work-nav-label">{{ $item['label'] }}</span>
                @if (isset($item['badge']))
                    <span class="nik-work-nav-badge">{{ $item['badge'] }}</span>
                @else
                    <span class="nik-work-nav-trailing"></span>
                @endif
            </a>
        @endforeach

        @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess() || \App\Filament\Resources\Departments\DepartmentResource::canAccess())
            <div class="nik-work-nav-section nik-work-sidebar-extra">Компания</div>

            @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess())
                <a href="{{ \App\Filament\Resources\AdminUsers\UserResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra" title="Сотрудники">
                    <span class="nik-work-nav-icon"><x-work.icon name="users" /></span>
                    <span class="nik-work-nav-label">Сотрудники</span>
                    <span class="nik-work-nav-trailing"></span>
                </a>
            @endif

            @if (\App\Filament\Resources\Departments\DepartmentResource::canAccess())
                <a href="{{ \App\Filament\Resources\Departments\DepartmentResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra" title="Организация">
                    <span class="nik-work-nav-icon"><x-work.icon name="building" /></span>
                    <span class="nik-work-nav-label">Организация</span>
                    <span class="nik-work-nav-trailing"></span>
                </a>
            @endif
        @endif
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
