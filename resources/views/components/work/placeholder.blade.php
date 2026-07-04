@props([
    'badge' => 'Скоро',
])

<div {{ $attributes->merge(['class' => 'nik-work-designed-empty']) }}>
    <div class="nik-work-row-title">{{ $slot }}</div>
    @if ($badge)
        <div style="margin-top: 10px;">
            <x-work.badge tone="gray">{{ $badge }}</x-work.badge>
        </div>
    @endif
</div>
