@props([
    'items' => [],
])

<div class="nik-work-timeline">
    @forelse ($items as $item)
        <div
            class="nik-work-timeline-row"
            @if (filled($item['workdayMarker'] ?? null))
                data-workday-marker="{{ $item['workdayMarker'] }}"
                data-workday-marker-time="{{ $item['time'] }}"
                @if (filled($item['workdayMarkerEnd'] ?? null))
                    data-workday-marker-end="{{ $item['workdayMarkerEnd'] }}"
                @endif
            @endif
        >
            <div class="nik-work-timeline-time">{{ $item['time'] }}</div>
            <div>
                <span class="nik-work-timeline-dot" style="background: {{ $item['color'] }}"></span>
            </div>
            <div>
                <div class="nik-work-row-title">{{ $item['title'] }}</div>
                <div class="nik-work-row-meta">{{ $item['meta'] }}</div>
            </div>
        </div>
    @empty
        <x-work.placeholder badge="">Сегодня смен и событий не назначено.</x-work.placeholder>
    @endforelse
</div>
