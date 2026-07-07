@props([
    'user' => auth()->user(),
    'initials' => null,
    'buttonClass' => 'nik-work-user-pill',
    'showChevron' => true,
])

@php
    $initials ??= $user
        ? collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->join('')
        : 'N';

    $avatarUrl = $user?->avatar_path ? asset('storage/'.$user->avatar_path) : null;
@endphp

<div
    {{ $attributes->class('nik-work-user-menu') }}
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
>
    <button
        type="button"
        class="{{ $buttonClass }}"
        aria-label="Профиль"
        x-bind:aria-expanded="open.toString()"
        x-on:click="open = ! open"
    >
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="" />
        @else
            <span>{{ $initials ?: 'N' }}</span>
        @endif

        @if ($showChevron)
            <span class="nik-work-user-chevron">⌄</span>
        @endif
    </button>

    <div
        class="nik-work-user-menu-panel"
        x-cloak
        x-show="open"
        x-transition.opacity.duration.150ms
        role="menu"
    >
        <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
            @csrf
            <button type="submit" role="menuitem">Выйти</button>
        </form>
    </div>
</div>
