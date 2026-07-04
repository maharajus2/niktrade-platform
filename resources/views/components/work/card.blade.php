@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<section {{ $attributes->merge(['class' => 'nik-work-card']) }}>
    @if ($title || isset($actions))
        <div class="nik-work-card-header">
            <div>
                @if ($title)
                    <div class="nik-work-card-title">
                        @if ($icon)
                            <span class="nik-work-symbol">
                                <x-work.icon :name="$icon" />
                            </span>
                        @endif
                        <span>{{ $title }}</span>
                    </div>
                @endif

                @if ($subtitle)
                    <div class="nik-work-card-subtitle">{{ $subtitle }}</div>
                @endif
            </div>

            {{ $actions ?? '' }}
        </div>
    @endif

    {{ $slot }}
</section>
