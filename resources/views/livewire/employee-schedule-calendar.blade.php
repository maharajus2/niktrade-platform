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
@endphp

<div
    class="nik-calendar"
    x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
    x-on:keydown.escape.window="closeSheet()"
>
    <section class="nik-calendar-shell">
        <header class="nik-calendar-hero">
            <div>
                <span>Универсальный календарь</span>
                <h2>{{ $employee->name }}</h2>
                <p>
                    {{ $employee->getScheduleTypeLabel() }} · смены, отсутствия, медосмотры, документы и будущие рабочие события.
                </p>
            </div>

            <div class="nik-calendar-hero-actions">
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

        <div class="nik-calendar-notices">
            @if (! $employee->schedule_type)
                <div class="is-amber">
                    <strong>Тип графика не указан</strong>
                    <span>Календарь доступен, но ручные смены отключены, кроме super admin.</span>
                </div>
            @elseif (! $canCreateShift)
                <div>
                    <strong>Смены создаются не вручную</strong>
                    <span>{{ $employee->getScheduleTypeLabel() }}: ручное создание смен отключено. Остальные события доступны при наличии прав.</span>
                </div>
            @endif

            <div>
                <strong>Заявки остаются основным процессом</strong>
                <span>Для отпуска, больничного или выходного сотрудник создаёт заявку. HR/руководитель может создавать события напрямую при наличии прав.</span>
            </div>
        </div>

        <div class="nik-calendar-grid">
            <div class="nik-calendar-main-card">
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
                                link.href = '{{ asset('css/employee-schedule-calendar.css') }}?v=20260706-unified'
                                link.dataset.ntEmployeeScheduleCss = 'true'

                                return link
                            })

                            await loadAsset('script[data-nt-employee-schedule-js]', () => {
                                const script = document.createElement('script')
                                script.src = '{{ asset('js/employee-schedule-calendar.js') }}?v=20260706-unified'
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
                                requestCreateUrl: @js(\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create')),
                            })
                        })()
                    "
                    class="nt-fullcalendar nik-calendar-fullcalendar"
                ></div>
            </div>

            <aside class="nik-calendar-side">
                <section>
                    <div class="nik-calendar-side-head">
                        <span>Сегодня</span>
                        <strong>Повестка дня</strong>
                    </div>
                    <div class="nik-calendar-agenda" data-nt-calendar-agenda>
                        <div class="nik-calendar-empty">События загружаются...</div>
                    </div>
                </section>

                <section>
                    <div class="nik-calendar-side-head">
                        <span>Фильтры</span>
                        <strong>Типы событий</strong>
                    </div>
                    <div class="nik-calendar-type-legend">
                        @foreach ($typeOptions as $type => $label)
                            <span class="is-{{ $type }}">{{ $label }}</span>
                        @endforeach
                    </div>
                </section>

                <section>
                    <div class="nik-calendar-side-head">
                        <span>Интеграции</span>
                        <strong>Будущие источники</strong>
                    </div>
                    <div class="nik-calendar-future-list">
                        <span>Geovision: факт прихода и ухода</span>
                        <span>Задачи: дедлайны</span>
                        <span>Документы: напоминания о сроках</span>
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
