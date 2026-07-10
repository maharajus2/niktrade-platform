@props([
    'title',
    'subtitle' => null,
    'user' => auth()->user(),
    'active' => 'workplace',
    'showSidebar' => true,
    'appClass' => '',
    'actions' => null,
    'greetingName' => null,
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
                    :greeting-name="$greetingName"
                />

                {{ $slot }}
            </div>
        </div>
    </div>
</div>

@once
    <script>
        (() => {
            const greetingForHour = (hour) => {
                if (hour >= 5 && hour < 12) {
                    return 'Доброе утро'
                }

                if (hour >= 12 && hour < 16) {
                    return 'Добрый день'
                }

                if (hour >= 16 && hour < 23) {
                    return 'Добрый вечер'
                }

                return 'Доброй ночи'
            }

            const updateWorkGreetings = () => {
                const greeting = greetingForHour(new Date().getHours())

                document.querySelectorAll('[data-work-greeting-title]').forEach((element) => {
                    const name = element.dataset.workGreetingName

                    if (!name) {
                        return
                    }

                    const suffix = element.textContent.includes('👋') ? ' 👋' : ''
                    element.textContent = `${greeting}, ${name}!${suffix}`
                })
            }

            updateWorkGreetings()
            window.addEventListener('focus', updateWorkGreetings)
            window.addEventListener('pageshow', updateWorkGreetings)
            setInterval(updateWorkGreetings, 60000)
        })()
    </script>
@endonce
