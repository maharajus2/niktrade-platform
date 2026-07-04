@props([
    'active' => 'workplace',
    'user' => null,
])

@php
    $items = [
        ['key' => 'home', 'label' => 'Главная', 'icon' => '⌂', 'url' => \App\Filament\Pages\Dashboard::getUrl()],
        ['key' => 'workplace', 'label' => 'Рабочее пространство', 'icon' => '▦', 'url' => \App\Filament\Pages\Workplace::getUrl()],
    ];

    $mainItems = [
        ['label' => 'Календарь', 'icon' => '▣', 'url' => \App\Filament\Pages\MyCalendar::getUrl()],
        ['label' => 'Задачи', 'icon' => '☑', 'url' => '#'],
        ['label' => 'Заявки', 'icon' => '□', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')],
        ['label' => 'Документы', 'icon' => '▤', 'url' => '#employee-documents'],
        ['label' => 'Сообщения', 'icon' => '○', 'url' => '#'],
    ];
@endphp

<aside class="nik-work-sidebar" aria-label="Рабочая навигация">
    <a href="{{ \App\Filament\Pages\Workplace::getUrl() }}" class="nik-work-logo">
        <span class="nik-work-logo-mark">N</span>
        <span>Niktrade</span>
    </a>

    <nav class="nik-work-nav">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}" class="nik-work-nav-link {{ $active === $item['key'] ? 'is-active' : '' }}">
                <span class="nik-work-nav-icon">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
                <span></span>
            </a>
        @endforeach

        <div class="nik-work-nav-section">Основное</div>

        @foreach ($mainItems as $item)
            <a href="{{ $item['url'] }}" class="nik-work-nav-link">
                <span class="nik-work-nav-icon">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
                <span></span>
            </a>
        @endforeach

        @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess() || \App\Filament\Resources\Departments\DepartmentResource::canAccess())
            <div class="nik-work-nav-section nik-work-sidebar-extra">Компания</div>

            @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess())
                <a href="{{ \App\Filament\Resources\AdminUsers\UserResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra">
                    <span class="nik-work-nav-icon">♙</span>
                    <span>Сотрудники</span>
                    <span></span>
                </a>
            @endif

            @if (\App\Filament\Resources\Departments\DepartmentResource::canAccess())
                <a href="{{ \App\Filament\Resources\Departments\DepartmentResource::getUrl('index') }}" class="nik-work-nav-link nik-work-sidebar-extra">
                    <span class="nik-work-nav-icon">⌘</span>
                    <span>Организация</span>
                    <span></span>
                </a>
            @endif
        @endif
    </nav>
</aside>
