@php
    $requestCounts = [
        'pending' => $employee->scheduleRequests()
            ->whereIn('status', [
                \App\Models\EmployeeScheduleRequest::STATUS_PENDING,
                \App\Models\EmployeeScheduleRequest::STATUS_IN_REVIEW,
                \App\Models\EmployeeScheduleRequest::STATUS_FORWARDED,
            ])
            ->whereNull('deleted_at')
            ->count(),
        'approved' => $employee->scheduleRequests()->where('status', \App\Models\EmployeeScheduleRequest::STATUS_APPROVED)->whereNull('deleted_at')->count(),
        'rejected' => $employee->scheduleRequests()->where('status', \App\Models\EmployeeScheduleRequest::STATUS_REJECTED)->whereNull('deleted_at')->count(),
        'returned' => $employee->scheduleRequests()->where('status', \App\Models\EmployeeScheduleRequest::STATUS_RETURNED)->whereNull('deleted_at')->count(),
    ];

    $recentRequests = $employee->scheduleRequests()
        ->whereNull('deleted_at')
        ->latest()
        ->take(4)
        ->get();

    $eventLegend = [
        'shift' => 'Смена',
        'day_off' => 'Выходной',
        'vacation' => 'Отпуск',
        'sick_leave' => 'Больничный',
        'business_trip' => 'Командировка',
        'training' => 'Обучение',
        'medical_exam' => 'Медосмотр',
        'custom' => 'Другое',
    ];

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Главная', 'icon' => 'home', 'url' => \App\Filament\Pages\Workplace::getUrl()],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => \App\Filament\Pages\MyCalendar::getUrl(), 'active' => true],
            ['label' => 'Заявки', 'icon' => 'link', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')],
        ],
        'Календарь' => [
            ['label' => 'Создать заявку', 'icon' => 'plus', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create')],
            ['label' => 'Задачи', 'icon' => 'check-square', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
            ['label' => 'Документы', 'icon' => 'file', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
        ],
        'Компания' => array_values(array_filter([
            \App\Filament\Resources\AdminUsers\UserResource::canAccess()
                ? ['label' => 'Сотрудники', 'icon' => 'users', 'url' => \App\Filament\Resources\AdminUsers\UserResource::getUrl('index')]
                : null,
            \App\Filament\Resources\Departments\DepartmentResource::canAccess()
                ? ['label' => 'Организация', 'icon' => 'building', 'url' => \App\Filament\Resources\Departments\DepartmentResource::getUrl('index')]
                : null,
        ])),
    ];

    $mobileCalendarStrip = collect($calendarDays)
        ->filter(fn ($day) => $day['isCurrentMonth'])
        ->values();
    $mobileWeekdays = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
@endphp

<div
    class="nik-calendar is-calendar-month"
    x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
    x-on:keydown.escape.window="closeSheet()"
>
    <section class="nik-calendar-shell">
        <header class="nik-calendar-hero">
            <div>
                @if (\App\Filament\Resources\AdminUsers\UserResource::canAccess())
                    <a class="nik-calendar-back" href="{{ \App\Filament\Resources\AdminUsers\UserResource::getUrl('index') }}">
                        <x-work.icon name="chevron-left" />
                        <span>Сотрудники</span>
                    </a>
                @endif
                <h1>Календарь сотрудника</h1>
                <p>
                    {{ $employee->name }} · {{ $employee->position ?: 'Должность не указана' }} · {{ $employee->department?->name ?: 'Отдел не указан' }}
                </p>
            </div>

            <div class="nik-calendar-hero-actions">
                <button type="button" class="nik-calendar-filter-button" data-nt-calendar-filter-toggle>
                    <x-work.icon name="filter" />
                    <span>Фильтры</span>
                </button>
                @if ($canUpdate)
                    <button type="button" class="nik-calendar-add" data-nt-calendar-add>
                        <x-work.icon name="plus" />
                        <span>Добавить событие</span>
                    </button>
                @else
                    <span class="nik-calendar-disabled-action">Нет прав на добавление</span>
                @endif
            </div>
        </header>

        <div class="nik-calendar-grid">
            <div class="nik-calendar-main-card">
                <div class="nik-calendar-toolbar">
                    <div class="nik-calendar-view-switch" aria-label="Режим календаря">
                        <button type="button" class="is-active" data-nt-calendar-view="dayGridMonth">Месяц</button>
                        <button type="button" data-nt-calendar-view="timeGridWeek">Неделя</button>
                        <button type="button" data-nt-calendar-view="timeGridDay">День</button>
                        <button type="button" data-nt-calendar-view="listWeek">Список</button>
                    </div>

                    <div class="nik-calendar-navigation">
                        <button type="button" data-nt-calendar-prev aria-label="Предыдущий период"><x-work.icon name="chevron-left" /></button>
                        <button type="button" class="nik-calendar-title-button" data-nt-calendar-title-toggle aria-label="Выбрать месяц и год">
                            <strong data-nt-calendar-title>{{ $monthLabel }}</strong>
                        </button>
                        <button type="button" data-nt-calendar-next aria-label="Следующий период"><x-work.icon name="chevron-right" /></button>
                        <button type="button" data-nt-calendar-today>Сегодня</button>
                    </div>

                    <div class="nik-calendar-period-picker" data-nt-period-picker hidden>
                        <div class="nik-calendar-period-months" data-nt-period-months></div>
                        <label>
                            <span>Год</span>
                            <select data-nt-period-year></select>
                        </label>
                    </div>
                </div>

                <div class="nik-calendar-filters" data-nt-calendar-filters>
                    <label>
                        <span>Тип события</span>
                        <select data-nt-filter="type">
                            <option value="">Все</option>
                            @foreach ($typeOptions as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Видимость</span>
                        <select data-nt-filter="visibility">
                            <option value="">Все</option>
                            @foreach ($visibilityOptions as $visibility => $label)
                                <option value="{{ $visibility }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Источник</span>
                        <select data-nt-filter="source">
                            <option value="">Все</option>
                            @foreach ($sourceOptions as $source => $label)
                                <option value="{{ $source }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="nik-calendar-archive-toggle">
                        <input type="checkbox" disabled>
                        <span>Показывать архив</span>
                    </label>
                </div>

                <div class="nik-calendar-mobile-strip">
                    @foreach ($mobileCalendarStrip as $day)
                        <button
                            type="button"
                            class="{{ $day['isCurrentMonth'] ? '' : 'is-muted' }} {{ $day['isToday'] ? 'is-active' : '' }} {{ $day['isWeekend'] ? 'is-weekend is-non-working' : '' }} {{ $day['holidayLabel'] ? 'is-holiday is-non-working' : '' }}"
                            data-nt-mobile-date="{{ $day['date']->toDateString() }}"
                            @if ($day['holidayLabel']) title="{{ $day['holidayLabel'] }}" @endif
                        >
                            <span>{{ $mobileWeekdays[$day['date']->dayOfWeekIso] }}</span>
                            <strong>{{ $day['date']->format('j') }}</strong>

                            @if (count($day['events']) > 0)
                                <i>
                                    @foreach ($day['events']->take(3) as $event)
                                        <b style="background: {{ $event->getCalendarColor() }}"></b>
                                    @endforeach
                                </i>
                            @endif
                        </button>
                    @endforeach
                </div>

                <button type="button" class="nik-calendar-mobile-expand" data-nt-mobile-calendar-expand aria-expanded="false">
                    <span>Развернуть календарь</span>
                    <x-work.icon name="chevron-down" />
                </button>

                <div
                    wire:ignore
                    x-data
                    x-init="
                        (async () => {
                            const loadAsset = (selector, createElement) => new Promise((resolve, reject) => {
                                const existing = document.querySelector(selector)

                                if (existing) {
                                    if (existing.dataset.loaded === 'true' || existing.tagName === 'LINK') {
                                        resolve()

                                        return
                                    }

                                    existing.addEventListener('load', () => resolve(), { once: true })
                                    existing.addEventListener('error', () => reject(new Error('Calendar asset failed to load.')), { once: true })

                                    return
                                }

                                const element = createElement()
                                element.onload = () => {
                                    element.dataset.loaded = 'true'
                                    resolve()
                                }
                                element.onerror = () => reject(new Error('Calendar asset failed to load.'))
                                document.head.appendChild(element)
                            })

                            await loadAsset('link[data-nt-employee-schedule-css]', () => {
                                const link = document.createElement('link')
                                link.rel = 'stylesheet'
                                link.href = '{{ asset('css/employee-schedule-calendar.css') }}?v=20260707-mobile-month-picker'
                                link.dataset.ntEmployeeScheduleCss = 'true'

                                return link
                            })

                            await loadAsset('script[data-nt-employee-schedule-js]', () => {
                                const script = document.createElement('script')
                                script.src = '{{ asset('js/employee-schedule-calendar.js') }}?v=20260707-mobile-month-picker'
                                script.dataset.ntEmployeeScheduleJs = 'true'

                                return script
                            })

                            window.niktradeEmployeeScheduleCalendar($el, $wire, {
                                canUpdate: @js($canUpdate),
                                canEditPast: @js($canEditPast),
                                canCreateShift: @js($canCreateShift),
                                isSuperAdmin: @js($isSuperAdmin),
                                scheduleType: @js($employee->schedule_type),
                                scheduleLabel: @js($employee->getScheduleTypeLabel()),
                                typeOptions: @js($typeOptions),
                                futureTypeOptions: @js($futureTypeOptions),
                                visibilityOptions: @js($visibilityOptions),
                                sourceOptions: @js($sourceOptions),
                                productionCalendar: @js($productionCalendar),
                                requestCreateUrl: @js(\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create')),
                            })
                        })()
                    "
                    class="nt-fullcalendar nik-calendar-fullcalendar"
                ></div>

                <div class="nik-calendar-legend">
                    @foreach ($eventLegend as $type => $label)
                        <span class="is-{{ $type }}">{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            <aside class="nik-calendar-side">
                <section class="nik-calendar-mini-card">
                    <div class="nik-calendar-side-head">
                        <span>{{ $monthLabel }}</span>
                        <strong>Мини-календарь</strong>
                    </div>
                    <x-work.calendar-mini
                        :month-label="$monthLabel"
                        :days="$calendarDays"
                        :url="\App\Filament\Pages\MyCalendar::getUrl()"
                    />
                </section>

                <section>
                    <div class="nik-calendar-side-head">
                        <span data-nt-selected-day-label>Сегодня</span>
                        <strong>События дня</strong>
                    </div>
                    <div class="nik-calendar-agenda" data-nt-calendar-agenda>
                        <div class="nik-calendar-empty">События загружаются...</div>
                    </div>
                </section>

                <section>
                    <div class="nik-calendar-side-head">
                        <span>Тип графика</span>
                        <strong>{{ $employee->getScheduleTypeLabel() }}</strong>
                    </div>
                    <div class="nik-calendar-schedule-state {{ $canCreateShift ? 'is-green' : '' }}">
                        @if (! $employee->schedule_type)
                            <strong>Тип графика не указан</strong>
                            <span>Ручные смены отключены, кроме super admin.</span>
                        @elseif ($canCreateShift)
                            <strong>Ручное назначение смен доступно</strong>
                            <span>Можно создавать и редактировать смены.</span>
                        @else
                            <strong>Смены создаются не вручную</strong>
                            <span>Остальные события доступны при наличии прав.</span>
                        @endif
                    </div>
                </section>

                <section>
                    <div class="nik-calendar-side-head">
                        <span>Действия</span>
                        <strong>Быстрые действия</strong>
                    </div>
                    <div class="nik-calendar-quick-actions">
                        <a href="{{ \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create') }}">
                            <x-work.icon name="plus" />
                            <span>Создать заявку</span>
                            <small>На отпуск, отгул или больничный</small>
                        </a>
                        <a href="{{ \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index') }}">
                            <x-work.icon name="link" />
                            <span>Посмотреть мои заявки</span>
                            <small>Открыть список заявок</small>
                        </a>
                        <span>
                            <x-work.icon name="calendar" />
                            <span>Geovision и задачи</span>
                            <small>Будущие интеграции</small>
                        </span>
                    </div>
                </section>
            </aside>
        </div>
    </section>

    <x-work.mobile-bottom-sheets
        :menu-groups="$mobileMenuGroups"
        :request-counts="$requestCounts"
        :recent-requests="$recentRequests"
        :create-request-url="\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create')"
        :requests-url="\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')"
    />
    <x-work.mobile-bottom-nav />
</div>
