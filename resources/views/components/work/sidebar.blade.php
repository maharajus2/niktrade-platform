@props([
    'active' => 'workplace',
    'user' => null,
])

@php
    $items = [
        ['key' => 'home', 'label' => 'Главная', 'icon' => 'home', 'url' => \App\Filament\Pages\Dashboard::getUrl()],
        ['key' => 'workplace', 'label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => \App\Filament\Pages\Workplace::getUrl()],
    ];

    $mainItems = [
        ['label' => 'Календарь', 'icon' => 'calendar', 'url' => \App\Filament\Pages\MyCalendar::getUrl()],
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
            <a href="{{ $item['url'] }}" class="nik-work-nav-link {{ $active === $item['key'] ? 'is-active' : '' }}">
                <span class="nik-work-nav-icon"><x-work.icon :name="$item['icon']" /></span>
                <span>{{ $item['label'] }}</span>
                <span></span>
            </a>
        @endforeach

        <div class="nik-work-nav-section">Основное</div>

        @foreach ($mainItems as $item)
            <a href="{{ $item['url'] }}" class="nik-work-nav-link">
                <span class="nik-work-nav-icon"><x-work.icon :name="$item['icon']" /></span>
                <span>{{ $item['label'] }}</span>
                @if (isset($item['badge']))
                    <span class="nik-work-nav-badge">{{ $item['badge'] }}</span>
                @else
                    <span></span>
                @endif
            </a>
        @endforeach

        @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess() || \App\Filament\Resources\Departments\DepartmentResource::canAccess())
            <div class="nik-work-nav-section nik-work-sidebar-extra">Компания</div>

            @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess())
                <a href="{{ \App\Filament\Resources\AdminUsers\UserResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra">
                    <span class="nik-work-nav-icon"><x-work.icon name="users" /></span>
                    <span>Сотрудники</span>
                    <span></span>
                </a>
            @endif

            @if (\App\Filament\Resources\Departments\DepartmentResource::canAccess())
                <a href="{{ \App\Filament\Resources\Departments\DepartmentResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra">
                    <span class="nik-work-nav-icon"><x-work.icon name="building" /></span>
                    <span>Организация</span>
                    <span></span>
                </a>
            @endif
        @endif
    </nav>

    <div class="nik-work-sidebar-user">
        <div class="nik-work-sidebar-avatar">{{ $initials }}</div>
        <div>
            <div class="nik-work-sidebar-name">{{ $user?->name ?? 'Niktrade' }}</div>
            <div class="nik-work-sidebar-role">Сотрудник</div>
        </div>
        <span aria-hidden="true">⌄</span>
    </div>
</aside>
