@props([
    'title',
    'subtitle' => null,
    'user' => auth()->user(),
    'active' => 'workplace',
    'showSidebar' => true,
    'appClass' => '',
    'actions' => null,
    'greetingName' => null,
    'renderedLocalDate' => null,
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

<div
    class="nik-work-app {{ $appClass }}"
    @if (filled($renderedLocalDate))
        data-work-rendered-local-date="{{ $renderedLocalDate }}"
    @endif
>
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
            const localDateString = () => {
                const date = new Date()
                const year = date.getFullYear()
                const month = String(date.getMonth() + 1).padStart(2, '0')
                const day = String(date.getDate()).padStart(2, '0')

                return `${year}-${month}-${day}`
            }

            const cookieValue = (name) => document.cookie
                .split('; ')
                .find((row) => row.startsWith(`${name}=`))
                ?.split('=')
                .slice(1)
                .join('=') || null

            const setCookie = (name, value) => {
                document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=604800; SameSite=Lax`
            }

            const syncWorkLocalDate = () => {
                const localDate = localDateString()
                const renderedDate = document.querySelector('[data-work-rendered-local-date]')?.dataset.workRenderedLocalDate
                const currentCookieDate = cookieValue('niktrade_local_date')

                if (currentCookieDate !== localDate) {
                    setCookie('niktrade_local_date', localDate)

                    if (renderedDate && renderedDate !== localDate) {
                        window.location.reload()
                    }

                    return
                }

                if (renderedDate && renderedDate !== localDate) {
                    const reloadKey = `niktrade-local-date-reloaded:${localDate}`

                    if (window.sessionStorage.getItem(reloadKey) !== '1') {
                        window.sessionStorage.setItem(reloadKey, '1')
                        window.location.reload()
                    }
                }
            }

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

            syncWorkLocalDate()
            updateWorkGreetings()
            window.addEventListener('focus', () => {
                syncWorkLocalDate()
                updateWorkGreetings()
            })
            window.addEventListener('pageshow', () => {
                syncWorkLocalDate()
                updateWorkGreetings()
            })
            setInterval(() => {
                syncWorkLocalDate()
                updateWorkGreetings()
            }, 60000)
        })()
    </script>
@endonce
