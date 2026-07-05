@php
    $employee = $workspace['employee'];
    $documents = $workspace['documents'];
    $requestCounts = $workspace['requestCounts'];
    $attentionItems = $workspace['attentionItems'];
    $firstName = str($employee->name)->before(' ')->value() ?: $employee->name;
    $initials = collect(explode(' ', trim($employee->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->join('');
@endphp

<div class="nik-work-mobile" aria-label="Мобильное рабочее пространство сотрудника">
    <header class="nik-work-mobile-top">
        <a href="{{ \App\Filament\Pages\Workplace::getUrl() }}" class="nik-work-mobile-brand" aria-label="Никтрейд">
            <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
        </a>

        <div class="nik-work-mobile-top-actions">
            <button type="button" class="nik-work-mobile-icon-button" aria-label="Поиск">
                <x-work.icon name="search" />
            </button>
            <button type="button" class="nik-work-mobile-icon-button has-badge" aria-label="Уведомления">
                <x-work.icon name="bell" />
                <span>3</span>
            </button>
            <div class="nik-work-mobile-avatar" aria-label="Профиль">{{ $initials }}</div>
        </div>
    </header>

    <section class="nik-work-mobile-hero">
        <h1>Добрый день, {{ $firstName }}! 👋</h1>
        <p>{{ $workspace['dateLabel'] }}</p>
    </section>

    <main class="nik-work-mobile-stack">
        <section class="nik-work-mobile-card nik-work-mobile-day-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="clock" /></span>
                    <strong>Мой день</strong>
                </div>
                <a href="{{ $workspace['urls']['calendar'] }}">Открыть</a>
            </div>

            <div class="nik-work-mobile-day">
                <div class="nik-work-mobile-shift">
                    <div class="nik-work-mobile-label">Ваша смена</div>
                    <div class="nik-work-mobile-shift-time">{{ $workspace['shiftLabel'] ?? 'Не назначена' }}</div>
                    <div class="nik-work-mobile-status">{{ $workspace['todayStatus'] }}</div>

                    <div class="nik-work-mobile-hours">
                        <span>Рабочее время сегодня</span>
                        <strong>{{ number_format($workspace['hoursToday'], 1, ',', ' ') }} ч</strong>
                        <small>из 8 ч</small>
                        <div class="nik-work-mobile-progress">
                            <i style="width: {{ min(100, ($workspace['hoursToday'] / 8) * 100) }}%;"></i>
                        </div>
                    </div>
                </div>

                <div class="nik-work-mobile-timeline">
                    @forelse ($workspace['timeline'] as $item)
                        <div class="nik-work-mobile-timeline-row">
                            <time>{{ $item['time'] }}</time>
                            <span style="background: {{ $item['color'] }}"></span>
                            <div>
                                <strong>{{ $item['title'] }}</strong>
                                <small>{{ $item['meta'] }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="nik-work-mobile-empty">На сегодня нет событий в календаре.</div>
                    @endforelse
                </div>
            </div>

            <a class="nik-work-mobile-link" href="{{ $workspace['urls']['calendar'] }}">Открыть календарь дня</a>
        </section>

        <section class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-title">
                <span class="is-amber"><x-work.icon name="alert" /></span>
                <strong>Требует внимания</strong>
            </div>

            <div class="nik-work-mobile-list">
                @forelse ($attentionItems as $item)
                    <div class="nik-work-mobile-notice is-{{ $item['tone'] }}">
                        <span><x-work.icon name="alert" /></span>
                        <div>
                            <strong>{{ $item['title'] }}</strong>
                            <small>{{ $item['text'] }}</small>
                        </div>
                        <i>›</i>
                    </div>
                @empty
                    <div class="nik-work-mobile-empty">На сегодня нет важных уведомлений.</div>
                @endforelse
            </div>
        </section>

        <section class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-title">
                <span><x-work.icon name="zap" /></span>
                <strong>Быстрые действия</strong>
            </div>

            <div class="nik-work-mobile-actions">
                <a href="{{ $workspace['urls']['createRequest'] }}">
                    <x-work.icon name="plus" />
                    <strong>Создать заявку</strong>
                    <i>›</i>
                </a>
                <a href="{{ $workspace['urls']['calendar'] }}">
                    <x-work.icon name="calendar" />
                    <strong>Открыть календарь</strong>
                    <i>›</i>
                </a>
                <a href="#employee-documents-mobile">
                    <x-work.icon name="file" />
                    <strong>Открыть документы</strong>
                    <i>›</i>
                </a>
                <span class="is-disabled">
                    <x-work.icon name="message" />
                    <strong>Мессенджер</strong>
                    <em>Скоро</em>
                </span>
            </div>
        </section>

        <section id="employee-calendar-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="calendar" /></span>
                    <strong>Календарь</strong>
                </div>
                <a href="{{ $workspace['urls']['calendar'] }}">Полный</a>
            </div>
            <x-work.calendar-mini
                :month-label="$workspace['monthLabel']"
                :days="$workspace['calendarDays']"
                :url="$workspace['urls']['calendar']"
            />
        </section>

        <section id="employee-tasks-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="check-square" /></span>
                    <strong>Мои задачи</strong>
                </div>
                <small>Скоро</small>
            </div>
            <div class="nik-work-mobile-chips">
                @foreach (['Все', 'Новые', 'В работе', 'На проверке', 'Готово'] as $filter)
                    <span>{{ $filter }}</span>
                @endforeach
            </div>
            <div class="nik-work-mobile-empty is-illustrated">
                <x-work.icon name="book" />
                <strong>Пока нет задач</strong>
                <span>Здесь будут отображаться ваши задачи и поручения.</span>
            </div>
        </section>

        <section id="employee-requests-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-title">
                <span><x-work.icon name="link" /></span>
                <strong>Мои заявки</strong>
            </div>
            <div class="nik-work-mobile-chips">
                <span class="is-active">Все {{ array_sum($requestCounts) }}</span>
                <span>Ожидают {{ $requestCounts['pending'] }}</span>
                <span>Одобрены {{ $requestCounts['approved'] }}</span>
                <span>Отклонены {{ $requestCounts['rejected'] }}</span>
                <span>Возвращены {{ $requestCounts['returned'] }}</span>
            </div>
            <div class="nik-work-mobile-list">
                @forelse ($workspace['recentRequests'] as $request)
                    <div class="nik-work-mobile-row">
                        <div>
                            <strong>{{ $request->getTypeLabel() }}</strong>
                            <small>{{ $request->getDateRangeLabel() }}</small>
                        </div>
                        <span>{{ $request->getStatusLabel() }}</span>
                        <i>›</i>
                    </div>
                @empty
                    <div class="nik-work-mobile-empty">У вас пока нет заявок.</div>
                @endforelse
            </div>
            <a class="nik-work-mobile-link" href="{{ $workspace['urls']['requests'] }}">Открыть все заявки</a>
        </section>

        <section id="employee-documents-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="file" /></span>
                    <strong>Мои документы</strong>
                </div>
                <strong class="nik-work-mobile-percent">{{ $documents['completeness'] }}%</strong>
            </div>
            <div class="nik-work-mobile-doc-summary">
                <span>Комплектность документов</span>
                <div class="nik-work-mobile-progress">
                    <i style="width: {{ $documents['completeness'] }}%;"></i>
                </div>
                <p>
                    Не хватает: {{ $documents['missingCount'] }}.
                    Истекают: {{ $documents['expiringCount'] }}.
                    Просрочены: {{ $documents['expiredCount'] }}.
                </p>
            </div>
            <a class="nik-work-mobile-link" href="#employee-documents-mobile">Открыть все документы</a>
        </section>

        <section id="employee-messages-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="message" /></span>
                    <strong>Мессенджер</strong>
                </div>
                <small>Скоро</small>
            </div>
            <div class="nik-work-mobile-empty">Корпоративный мессенджер будет подключён позже.</div>
        </section>

        <section id="employee-more-mobile" class="nik-work-mobile-card">
            <div class="nik-work-mobile-card-head">
                <div class="nik-work-mobile-card-title">
                    <span><x-work.icon name="grid" /></span>
                    <strong>Ещё</strong>
                </div>
                <button type="button">Редактировать</button>
            </div>

            <div class="nik-work-mobile-group-list">
                <div>
                    @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess())
                        <a href="{{ \App\Filament\Resources\AdminUsers\UserResource::getUrl('index') }}"><x-work.icon name="users" /><span>Сотрудники</span><i>›</i></a>
                    @endif
                    @if (\App\Filament\Resources\Departments\DepartmentResource::canAccess())
                        <a href="{{ \App\Filament\Resources\Departments\DepartmentResource::getUrl('index') }}"><x-work.icon name="building" /><span>Организация</span><i>›</i></a>
                    @endif
                    <a href="{{ $workspace['urls']['calendar'] }}"><x-work.icon name="calendar" /><span>Календарь</span><i>›</i></a>
                    <a href="{{ $workspace['urls']['requests'] }}"><x-work.icon name="link" /><span>Заявки сотрудников</span><i>›</i></a>
                </div>
                <div>
                    <a href="#employee-documents-mobile"><x-work.icon name="file" /><span>Документы</span><i>›</i></a>
                    <span><x-work.icon name="book" /><span>Справочники</span><em>Скоро</em></span>
                    <span><x-work.icon name="message" /><span>Мессенджер</span><em>Скоро</em></span>
                </div>
            </div>
        </section>
    </main>

    <nav class="nik-work-mobile-tabs" aria-label="Основная навигация">
        <a class="is-active" href="{{ \App\Filament\Pages\Workplace::getUrl() }}"><x-work.icon name="home" /><span>Главная</span></a>
        <a href="#employee-tasks-mobile"><x-work.icon name="check-square" /><span>Задачи</span></a>
        <a href="{{ $workspace['urls']['requests'] }}"><x-work.icon name="link" /><span>Заявки</span></a>
        <a href="#employee-more-mobile"><x-work.icon name="grid" /><span>Ещё</span></a>
    </nav>

    <nav class="nik-work-mobile-dock" aria-label="Быстрые действия">
        <a href="{{ $workspace['urls']['createRequest'] }}"><x-work.icon name="plus" /><span>Заявка</span></a>
        <a href="{{ $workspace['urls']['calendar'] }}"><x-work.icon name="calendar" /><span>Календарь</span></a>
        <a href="#employee-documents-mobile"><x-work.icon name="file" /><span>Документы</span></a>
        <a class="is-disabled" href="#employee-messages-mobile"><x-work.icon name="message" /><span>Мессенджер</span><i></i></a>
    </nav>
</div>
