@props([
    'tone' => 'blue',
])

<span {{ $attributes->class([
    'nik-work-badge',
    'is-green' => $tone === 'green',
    'is-red' => $tone === 'red',
    'is-amber' => $tone === 'amber',
    'is-gray' => $tone === 'gray',
]) }}>
    {{ $slot }}
</span>
