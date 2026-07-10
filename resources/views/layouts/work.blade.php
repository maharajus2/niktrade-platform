@props([
    'title',
    'subtitle' => null,
    'user' => auth()->user(),
    'active' => 'workplace',
    'showSidebar' => true,
    'appClass' => '',
    'actions' => null,
])

@php
    $initials = $user?->initials ?: 'N';
@endphp

@vite(['resources/css/app.css', 'resources/js/app.js'])

@once
    <style>
        {!! collect(file(resource_path('css/work.css')))
            ->reject(function (string $line): bool {
                $line = ltrim($line);

                return str_starts_with($line, '@import')
                    || str_starts_with($line, '@source');
            })
            ->implode('') !!}
    </style>
    <style>
        {!! file_get_contents(resource_path('css/work-liquid.css')) !!}
    </style>
@endonce

<div class="nik-work-app {{ $appClass }}">
    <div class="nik-work-shell">
        <div class="nik-work-layout {{ $showSidebar ? '' : 'nik-work-layout--no-sidebar' }}">
            @if ($showSidebar)
                <x-work.sidebar :active="$active" :user="$user" />
            @endif

            <div class="nik-work-main">
                <x-work.header
                    :title="$title"
                    :subtitle="$subtitle"
                    :initials="$initials"
                    :user="$user"
                    :actions="$actions"
                />

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
