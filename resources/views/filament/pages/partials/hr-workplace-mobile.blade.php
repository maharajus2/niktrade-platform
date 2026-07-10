@php
    $employee = $workspace['employee'];
    $firstName = $employee->greeting_name;
    $initials = $employee->initials;

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => \App\Filament\Pages\Workplace::getUrl(), 'active' => true],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => $workspace['urls']['calendar']],
            ['label' => 'Заявки сотрудников', 'icon' => 'link', 'url' => $workspace['urls']['requests']],
        ],
        'HR' => array_values(array_filter([
            \App\Filament\Resources\AdminUsers\UserResource::canAccess()
                ? ['label' => 'Сотрудники', 'icon' => 'users', 'url' => $workspace['urls']['employees']]
                : null,
            \App\Filament\Resources\Departments\DepartmentResource::canAccess()
                ? ['label' => 'Отделы', 'icon' => 'building', 'url' => $workspace['urls']['departments']]
                : null,
            ['label' => 'Документы', 'icon' => 'file', 'url' => $workspace['urls']['employees']],
        ])),
        'Инструменты' => [
            ['label' => 'Задачи', 'icon' => 'check-square', 'url' => '#', 'badge' => 'Скоро', 'sheet' => 'tasks'],
            ['label' => 'Сообщения', 'icon' => 'message', 'url' => '#', 'badge' => '2', 'sheet' => 'messages'],
        ],
    ];
@endphp

<div
    class="nik-work-hr-mobile"
    aria-label="Мобильное HR рабочее пространство"
    x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
    x-on:keydown.escape.window="closeSheet()"
>
    <header class="nik-work-hr-mobile-top">
        <a href="{{ \App\Filament\Pages\Workplace::getUrl() }}" class="nik-work-hr-mobile-brand" aria-label="Никтрейд">
            <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
        </a>

        <div class="nik-work-hr-mobile-actions">
            <button type="button" aria-label="Поиск"><x-work.icon name="search" /></button>
            <button type="button" class="has-badge" aria-label="Уведомления"><x-work.icon name="bell" /><span>3</span></button>
            <x-work.user-menu :user="$employee" :initials="$initials" button-class="nik-work-hr-mobile-avatar" :show-chevron="false" />
        </div>
    </header>

    <section class="nik-work-hr-mobile-hero">
        <div>
            <h1>Доброе утро, {{ $firstName }}!</h1>
            <p>Панель HR-специалиста</p>
        </div>

        @if ($contextAction)
            <div class="nik-work-hr-mobile-context">
                {{ $contextAction }}
            </div>
        @endif
    </section>

    <section class="nik-work-hr-mobile-kpis" aria-label="Ключевые показатели HR">
        @foreach ($workspace['kpis'] as $kpi)
            <article class="is-{{ $kpi['tone'] }}">
                <span><x-work.icon :name="$kpi['icon']" /></span>
                <small>{{ $kpi['label'] }}</small>
                <strong>{{ $kpi['value'] }}</strong>
                <em>{{ $kpi['delta'] }}</em>
            </article>
        @endforeach
    </section>

    <main class="nik-work-hr-mobile-stack">
        <section class="nik-work-hr-mobile-card">
            <div class="nik-work-hr-mobile-card-head">
                <div>
                    <span>На согласовании</span>
                    <h2>Заявки сотрудников</h2>
                </div>
                <a href="{{ $workspace['urls']['requests'] }}">Все</a>
            </div>

            <div class="nik-work-hr-mobile-list">
                @forelse ($workspace['requests'] as $request)
                    <a href="{{ $request['url'] }}" class="nik-work-hr-mobile-row">
                        <span class="nik-work-hr-mobile-avatar">{{ mb_substr($request['employee'], 0, 1) }}</span>
                        <div>
                            <strong>{{ $request['employee'] }}</strong>
                            <small>{{ $request['type'] }}</small>
                        </div>
                        <time>{{ $request['period'] }}</time>
                        <em>{{ $request['status'] }}</em>
                    </a>
                @empty
                    <div class="nik-work-hr-mobile-empty">Активных заявок нет.</div>
                @endforelse
            </div>
        </section>

        <section class="nik-work-hr-mobile-card">
            <div class="nik-work-hr-mobile-card-head">
                <div>
                    <span>{{ now()->translatedFormat('F Y') }}</span>
                    <h2>Плановые отсутствия</h2>
                </div>
                <a href="{{ $workspace['urls']['calendar'] }}">Календарь</a>
            </div>

            <div class="nik-work-hr-mobile-calendar-strip">
                @foreach (range(0, 6) as $offset)
                    @php($day = today()->copy()->addDays($offset))
                    <a href="{{ $workspace['urls']['calendar'] }}" class="{{ $day->isToday() ? 'is-active' : '' }}">
                        <span>{{ mb_substr($day->translatedFormat('D'), 0, 2) }}</span>
                        <strong>{{ $day->format('d') }}</strong>
                    </a>
                @endforeach
            </div>

            <div class="nik-work-hr-mobile-list">
                @forelse ($workspace['upcomingEvents'] as $event)
                    <a href="{{ $event['url'] ?? $workspace['urls']['calendar'] }}" class="nik-work-hr-mobile-event is-{{ $event['tone'] }}">
                        <div>
                            <strong>{{ $event['label'] }}</strong>
                            <small>{{ $event['employee'] }}</small>
                        </div>
                        <time>
                            @if (! $event['date']->isSameDay($event['endDate']))
                                {{ $event['date']->format('d.m') }}-{{ $event['endDate']->format('d.m') }}
                            @else
                                {{ $event['date']->format('d.m') }}
                            @endif
                        </time>
                    </a>
                @empty
                    <div class="nik-work-hr-mobile-empty">На ближайшие дни отсутствий нет.</div>
                @endforelse
            </div>
        </section>

        <section class="nik-work-hr-mobile-card">
            <div class="nik-work-hr-mobile-card-head">
                <div>
                    <span>Ближайшие 7 дней</span>
                    <h2>Дни рождения</h2>
                </div>
                <span class="nik-work-hr-mobile-gift"><x-work.icon name="calendar" /></span>
            </div>

            <div class="nik-work-hr-mobile-list">
                @forelse ($workspace['birthdays'] as $birthday)
                    <a href="{{ $birthday['url'] }}" class="nik-work-hr-mobile-row">
                        <span class="nik-work-hr-mobile-avatar">{{ mb_substr($birthday['employee'], 0, 1) }}</span>
                        <div>
                            <strong>{{ $birthday['employee'] }}</strong>
                            <small>{{ $birthday['date']->translatedFormat('d F') }} · {{ $birthday['age'] }}</small>
                        </div>
                        <i>›</i>
                    </a>
                @empty
                    <div class="nik-work-hr-mobile-empty">На ближайшую неделю дней рождения нет.</div>
                @endforelse
            </div>
        </section>

        <section class="nik-work-hr-mobile-card">
            <div class="nik-work-hr-mobile-card-head">
                <div>
                    <span>Ежедневная работа</span>
                    <h2>Быстрые действия</h2>
                </div>
            </div>

            <div class="nik-work-hr-mobile-action-grid">
                <a href="{{ $workspace['urls']['createEmployee'] }}"><x-work.icon name="users" /><span>Добавить сотрудника</span></a>
                <a href="{{ $workspace['urls']['createRequest'] }}"><x-work.icon name="plus" /><span>Создать заявку</span></a>
                <a href="{{ $workspace['urls']['employees'] }}"><x-work.icon name="file" /><span>Документы</span></a>
                <a href="{{ $workspace['urls']['departments'] }}"><x-work.icon name="building" /><span>Структура</span></a>
            </div>
        </section>
    </main>

    <x-work.mobile-bottom-sheets
        :menu-groups="$mobileMenuGroups"
        :create-request-url="$workspace['urls']['createRequest']"
        :requests-url="$workspace['urls']['requests']"
    />
    <x-work.mobile-bottom-nav />
</div>
