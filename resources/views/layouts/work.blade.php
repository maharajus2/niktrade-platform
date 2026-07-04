@props([
    'title',
    'subtitle' => null,
    'user' => auth()->user(),
    'active' => 'workplace',
])

@php
    $initials = $user
        ? collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->join('')
        : 'N';
@endphp

@vite('resources/css/work.css')

<div class="nik-work-app">
    <div class="nik-work-shell">
        <div class="nik-work-layout">
            <x-work.sidebar :active="$active" :user="$user" />

            <div class="nik-work-main">
                <x-work.header
                    :title="$title"
                    :subtitle="$subtitle"
                    :initials="$initials"
                />

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
