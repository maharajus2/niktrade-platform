@php
    use App\Filament\Pages\MyCalendar;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\AdminUsers\UserResource;
    use App\Filament\Resources\Departments\DepartmentResource;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
    use App\Models\User;
    use Illuminate\Support\Facades\Storage;

    $page = $employeesPage;
    $employees = $page['employees'];
    $statusTone = fn (?string $status): string => match ($status) {
        User::STATUS_VACATION => 'violet',
        User::STATUS_SICK_LEAVE => 'red',
        User::STATUS_DISMISSED => 'red',
        User::STATUS_ARCHIVED => 'gray',
        default => 'green',
    };
    $avatar = fn (User $employee): ?string => $employee->avatar_path ? Storage::disk('public')->url($employee->avatar_path) : null;
    $initials = fn (User $employee): string => $employee->initials;
    $queryWithout = fn (array $replace = [], array $remove = []): array => collect(request()->query())
        ->except($remove)
        ->merge($replace)
        ->filter(fn ($value) => filled($value))
        ->all();
    $menuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => Workplace::getUrl()],
            ['label' => 'Сотрудники', 'icon' => 'users', 'url' => UserResource::getUrl('index'), 'active' => true],
            ['label' => 'Отделы', 'icon' => 'building', 'url' => DepartmentResource::getUrl('index')],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
        ],
        'HR' => [
            ['label' => 'Заявки сотрудников', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index')],
            ['label' => 'Документы', 'icon' => 'file', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
            ['label' => 'Отчёты', 'icon' => 'list', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
        ],
    ];
@endphp

@component('layouts.work', [
    'title' => 'Сотрудники',
    'subtitle' => 'Управление сотрудниками и структурой компании',
    'user' => auth()->user(),
    'active' => 'employees',
    'showSidebar' => true,
    'appClass' => 'nik-work-app--employees',
])
    <main class="nik-employee-list nik-work-desktop">
        <section class="nik-directory-head">
            <div>
                <span>HR пространство · Сотрудники</span>
                <h1>Сотрудники</h1>
                <p>Операционный список сотрудников, статусов, отделов и быстрых HR-действий.</p>
            </div>
            <form class="nik-directory-search" method="GET" action="{{ $page['urls']['index'] }}">
                @foreach (request()->except(['q', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <x-work.icon name="search" />
                <input name="q" value="{{ $page['query']['q'] }}" placeholder="Поиск сотрудника..." />
            </form>
            <a class="nik-directory-filter-button" href="#employee-filters"><x-work.icon name="filter" /> Фильтры</a>
            @if ($page['canCreate'])
                <a class="nik-directory-primary" href="{{ $page['urls']['create'] }}"><x-work.icon name="plus" /> Добавить сотрудника</a>
            @endif
        </section>

        <section class="nik-directory-kpis">
            @foreach ($page['kpis'] as $kpi)
                <article class="is-{{ $kpi['tone'] }}">
                    <span><x-work.icon :name="$kpi['icon']" /></span>
                    <div>
                        <small>{{ $kpi['label'] }}</small>
                        <strong>{{ $kpi['value'] }}</strong>
                        <em>{{ $kpi['delta'] }}</em>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="nik-directory-layout">
            <div class="nik-directory-panel">
                <nav class="nik-directory-tabs" aria-label="Статусы сотрудников">
                    @foreach ($page['tabs'] as $key => $tab)
                        <a class="{{ $page['activeTab'] === $key ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['tab' => $key, 'page' => null])) }}">
                            {{ $tab['label'] }} <span>{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div id="employee-filters" class="nik-directory-filters">
                    <form method="GET" action="{{ $page['urls']['index'] }}">
                        <input type="hidden" name="tab" value="{{ $page['activeTab'] }}">
                        <label>
                            <span>Отдел</span>
                            <select name="department">
                                <option value="">Все отделы</option>
                                @foreach ($page['departments'] as $department)
                                    <option value="{{ $department->id }}" @selected((string) $page['query']['department'] === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>График</span>
                            <select name="schedule">
                                <option value="">Все</option>
                                @foreach (User::scheduleTypeOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected($page['query']['schedule'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Тип занятости</span>
                            <select name="employment_type">
                                <option value="">Все</option>
                                @foreach (User::employmentTypeOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected($page['query']['employment_type'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Роль</span>
                            <select name="role">
                                <option value="">Все</option>
                                @foreach ($page['roles'] as $value => $label)
                                    <option value="{{ $value }}" @selected($page['query']['role'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit"><x-work.icon name="filter" /> Применить</button>
                        <a href="{{ $page['urls']['index'] }}">Сбросить</a>
                    </form>
                </div>

                <div class="nik-directory-viewbar">
                    <strong>Список сотрудников</strong>
                    <div>
                        <a class="{{ $page['view'] === 'table' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['view' => 'table'])) }}"><x-work.icon name="list" /></a>
                        <a class="{{ $page['view'] === 'cards' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['view' => 'cards'])) }}"><x-work.icon name="grid" /></a>
                    </div>
                </div>

                @if ($page['view'] === 'cards')
                    <div class="nik-employee-card-grid">
                        @forelse ($employees as $employee)
                            <a href="{{ UserResource::getUrl('view', ['record' => $employee]) }}" class="nik-employee-card">
                                <span class="nik-directory-avatar">
                                    @if ($avatar($employee))
                                        <img src="{{ $avatar($employee) }}" alt="{{ $employee->name }}">
                                    @else
                                        <i>{{ $initials($employee) }}</i>
                                    @endif
                                </span>
                                <strong>{{ $employee->name }}</strong>
                                <small>{{ $employee->position ?: 'Должность не указана' }}</small>
                                <em>{{ $employee->department?->name ?: 'Без отдела' }}</em>
                                <x-work.badge :tone="$statusTone($employee->employment_status ?? $employee->employee_status)">{{ $employee->getEmploymentStatusLabel() }}</x-work.badge>
                            </a>
                        @empty
                            <div class="nik-directory-empty">Сотрудники не найдены.</div>
                        @endforelse
                    </div>
                @else
                    <div class="nik-directory-table">
                        <div class="nik-directory-table-head">
                            <span>Сотрудник</span><span>Отдел</span><span>График</span><span>Статус</span><span>Дата приёма</span><span>Контакты</span><span></span>
                        </div>
                        @forelse ($employees as $employee)
                            <a href="{{ UserResource::getUrl('view', ['record' => $employee]) }}" class="nik-directory-row">
                                <span class="nik-directory-person">
                                    <span class="nik-directory-avatar">
                                        @if ($avatar($employee))
                                            <img src="{{ $avatar($employee) }}" alt="{{ $employee->name }}">
                                        @else
                                            <i>{{ $initials($employee) }}</i>
                                        @endif
                                    </span>
                                    <span><strong>{{ $employee->name }}</strong><small>{{ $employee->position ?: 'Должность не указана' }}</small></span>
                                </span>
                                <span>{{ $employee->department?->name ?: 'Без отдела' }}</span>
                                <span><em class="nik-directory-pill">{{ $employee->getScheduleTypeLabel() }}</em></span>
                                <span><x-work.badge :tone="$statusTone($employee->employment_status ?? $employee->employee_status)">{{ $employee->getEmploymentStatusLabel() }}</x-work.badge></span>
                                <span>{{ $employee->hire_date?->format('d.m.Y') ?: '—' }}</span>
                                <span class="nik-directory-actions"><x-work.icon name="file" /><x-work.icon name="message" /></span>
                                <span class="nik-directory-more">›</span>
                            </a>
                        @empty
                            <div class="nik-directory-empty">Сотрудники не найдены.</div>
                        @endforelse
                    </div>
                @endif

                <div class="nik-directory-pagination">{{ $employees->links() }}</div>
            </div>

            <aside class="nik-directory-side">
                <section>
                    <h2>По отделам</h2>
                    <div class="nik-directory-donut"><strong>{{ $page['tabs']['all']['count'] }}</strong><span>Всего</span></div>
                    <div class="nik-directory-bars">
                        @foreach ($page['departmentStats'] as $stat)
                            <a href="{{ $page['urls']['index'].'?department='.$stat['department']->id }}">
                                <span>{{ $stat['department']->name }}</span>
                                <em>{{ $stat['count'] }} · {{ $stat['percent'] }}%</em>
                                <i style="width: {{ $stat['percent'] }}%"></i>
                            </a>
                        @endforeach
                    </div>
                    <a class="nik-directory-side-link" href="{{ $page['urls']['departments'] }}">Смотреть структуру</a>
                </section>
                <section>
                    <h2>Ближайшие дни рождения</h2>
                    <div class="nik-directory-mini-list">
                        @forelse ($page['birthdays'] as $birthday)
                            <a href="{{ UserResource::getUrl('view', ['record' => $birthday['employee']]) }}">
                                <span class="nik-directory-avatar is-small">
                                    @if ($avatar($birthday['employee']))
                                        <img src="{{ $avatar($birthday['employee']) }}" alt="{{ $birthday['employee']->name }}">
                                    @else
                                        <i>{{ $initials($birthday['employee']) }}</i>
                                    @endif
                                </span>
                                <strong>{{ $birthday['employee']->name }}</strong>
                                <em>{{ $birthday['date']->translatedFormat('d F') }}</em>
                            </a>
                        @empty
                            <p>Ближайших дней рождения нет.</p>
                        @endforelse
                    </div>
                </section>
                <section>
                    <h2>Быстрые действия</h2>
                    <div class="nik-directory-quick">
                        @if ($page['canCreate'])
                            <a href="{{ $page['urls']['create'] }}"><x-work.icon name="plus" /> Добавить сотрудника</a>
                        @endif
                        <a href="{{ $page['urls']['createRequest'] }}"><x-work.icon name="link" /> Создать заявку</a>
                        <span><x-work.icon name="archive" /> Импорт сотрудников <em>Скоро</em></span>
                        <span><x-work.icon name="list" /> Кадровые отчёты <em>Скоро</em></span>
                    </div>
                </section>
            </aside>
        </section>
    </main>

    <div class="nik-work-mobile nik-directory-mobile nik-employee-list-mobile" x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }">
        <header class="nik-directory-mobile-top">
            <a href="{{ Workplace::getUrl() }}"><img src="{{ asset('images/logo-icon.png') }}" alt="Никтрейд"><strong>Сотрудники</strong></a>
            <div><button><x-work.icon name="search" /></button><button><x-work.icon name="bell" /></button></div>
        </header>
        <h1>Сотрудники</h1>
        <section class="nik-directory-mobile-kpis">
            @foreach (array_slice($page['kpis'], 0, 6) as $kpi)
                <article><small>{{ $kpi['label'] }}</small><strong>{{ $kpi['value'] }}</strong><em>{{ $kpi['delta'] }}</em></article>
            @endforeach
        </section>
        <nav class="nik-directory-mobile-tabs">
            @foreach ($page['tabs'] as $key => $tab)
                <a class="{{ $page['activeTab'] === $key ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['tab' => $key, 'page' => null])) }}">{{ $tab['label'] }} <span>{{ $tab['count'] }}</span></a>
            @endforeach
        </nav>
        <form class="nik-directory-mobile-search" method="GET" action="{{ $page['urls']['index'] }}">
            <input name="q" value="{{ $page['query']['q'] }}" placeholder="Поиск сотрудника..." />
            <a href="#employee-filters"><x-work.icon name="filter" /></a>
        </form>
        <section class="nik-directory-mobile-list">
            @forelse ($employees as $employee)
                <a href="{{ UserResource::getUrl('view', ['record' => $employee]) }}" class="nik-directory-mobile-card">
                    <span class="nik-directory-avatar">
                        @if ($avatar($employee))
                            <img src="{{ $avatar($employee) }}" alt="{{ $employee->name }}">
                        @else
                            <i>{{ $initials($employee) }}</i>
                        @endif
                    </span>
                    <div><strong>{{ $employee->name }}</strong><small>{{ $employee->position ?: 'Должность не указана' }} · {{ $employee->department?->name ?: 'Без отдела' }}</small></div>
                    <x-work.badge :tone="$statusTone($employee->employment_status ?? $employee->employee_status)">{{ $employee->getEmploymentStatusLabel() }}</x-work.badge>
                </a>
            @empty
                <div class="nik-directory-empty">Сотрудники не найдены.</div>
            @endforelse
        </section>
        <x-work.mobile-bottom-sheets :menu-groups="$menuGroups" :create-request-url="$page['urls']['createRequest']" :requests-url="\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')" />
        <x-work.mobile-bottom-nav />
    </div>
@endcomponent
