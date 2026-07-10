@php
    use App\Filament\Pages\MyCalendar;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\AdminUsers\UserResource;
    use App\Filament\Resources\Departments\DepartmentResource;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;

    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->join('')
        : 'N';

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => Workplace::getUrl()],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
            ['label' => 'Заявки', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index'), 'active' => true],
        ],
        'Заявки' => [
            ['label' => 'Создать заявку', 'icon' => 'plus', 'url' => EmployeeScheduleRequestResource::getUrl('create'), 'active' => true],
            ['label' => 'Мои заявки', 'icon' => 'file', 'url' => EmployeeScheduleRequestResource::getUrl('index')],
        ],
        'Компания' => array_values(array_filter([
            UserResource::canAccess()
                ? ['label' => 'Сотрудники', 'icon' => 'users', 'url' => UserResource::getUrl('index')]
                : null,
            DepartmentResource::canAccess()
                ? ['label' => 'Отделы', 'icon' => 'building', 'url' => DepartmentResource::getUrl('index')]
                : null,
        ])),
    ];
@endphp

<x-filament-panels::page>
    @component('layouts.work', [
        'title' => 'Создать заявку',
        'subtitle' => 'Оформление отпуска, отгула, смены или события',
        'user' => $user,
        'active' => 'requests',
        'showSidebar' => true,
        'appClass' => 'nik-work-app--employee nik-work-app--schedule-request',
    ])
        <div
            class="nik-schedule-request"
            x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
            x-on:keydown.escape.window="closeSheet()"
        >
            <header class="nik-work-mobile-top nik-schedule-request-mobile-head">
                <a href="{{ Workplace::getUrl() }}" class="nik-work-mobile-brand" aria-label="Никтрейд">
                    <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
                </a>
                <div class="nik-work-mobile-top-actions">
                    <button type="button" class="nik-work-mobile-icon-button" aria-label="Поиск"><x-work.icon name="search" /></button>
                    <button type="button" class="nik-work-mobile-icon-button has-badge" aria-label="Уведомления"><x-work.icon name="bell" /><span>3</span></button>
                    <x-work.user-menu :user="$user" :initials="$initials" button-class="nik-work-mobile-avatar" :show-chevron="false" />
                </div>
            </header>

            <section class="nik-schedule-request-hero">
                <div>
                    <a class="nik-schedule-request-back" href="{{ EmployeeScheduleRequestResource::getUrl('index') }}">
                        <x-work.icon name="chevron-left" />
                        <span>К заявкам</span>
                    </a>
                    <span class="nik-schedule-request-eyebrow">Личное дело сотрудника</span>
                    <h1>Создать заявку</h1>
                    <p>Выберите сотрудника, тип заявки и период. После отправки заявка попадёт в маршрут согласования.</p>
                </div>

                <div class="nik-schedule-request-actions">
                    <a class="nik-schedule-request-action" href="{{ MyCalendar::getUrl() }}">
                        <x-work.icon name="calendar" />
                        <span>К календарю</span>
                    </a>
                    <a class="nik-schedule-request-action" href="{{ EmployeeScheduleRequestResource::getUrl('index') }}">
                        <x-work.icon name="link" />
                        <span>Все заявки</span>
                    </a>
                </div>
            </section>

            <main class="nik-schedule-request-layout">
                <section class="nik-schedule-request-card nik-schedule-request-form-card">
                    <div class="nik-schedule-request-section-head">
                        <div>
                            <span>Новая заявка</span>
                            <h2>Параметры</h2>
                        </div>
                    </div>

                    <div class="nik-schedule-request-form">
                        {{ $this->content }}
                    </div>
                </section>

                <aside class="nik-schedule-request-side">
                    <section class="nik-schedule-request-card">
                        <div class="nik-schedule-request-section-head">
                            <div>
                                <span>Подсказка</span>
                                <h2>Как это работает</h2>
                            </div>
                        </div>
                        <div class="nik-schedule-request-help">
                            <div><span><x-work.icon name="calendar" /></span><p>Отпуск, больничный и отгул обычно создаются на весь день.</p></div>
                            <div><span><x-work.icon name="clock" /></span><p>Для смены укажите начало и окончание в пределах одного дня.</p></div>
                            <div><span><x-work.icon name="check-circle" /></span><p>После согласования событие появится в календаре сотрудника.</p></div>
                        </div>
                    </section>
                </aside>
            </main>

            <x-work.mobile-bottom-sheets
                :menu-groups="$mobileMenuGroups"
                :create-request-url="EmployeeScheduleRequestResource::getUrl('create')"
                :requests-url="EmployeeScheduleRequestResource::getUrl('index')"
            />
            <x-work.mobile-bottom-nav />
        </div>
    @endcomponent
</x-filament-panels::page>
