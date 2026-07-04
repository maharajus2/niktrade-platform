@props([
    'href' => null,
    'icon' => null,
    'label',
    'primary' => false,
    'disabled' => false,
    'suffix' => '→',
])

@if ($disabled || ! $href)
    <span {{ $attributes->class(['nik-work-action', 'is-muted' => $disabled]) }}>
        <span class="nik-work-action-icon">
            @if ($icon)
                <x-work.icon :name="$icon" />
            @endif
        </span>
        <span>{{ $label }}</span>
        <span>{{ $suffix }}</span>
    </span>
@else
    <a href="{{ $href }}" {{ $attributes->class(['nik-work-action', 'is-primary' => $primary]) }}>
        <span class="nik-work-action-icon">
            @if ($icon)
                <x-work.icon :name="$icon" />
            @endif
        </span>
        <span>{{ $label }}</span>
        <span>{{ $suffix }}</span>
    </a>
@endif
