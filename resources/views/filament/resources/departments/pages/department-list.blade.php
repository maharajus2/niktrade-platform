@php
    use App\Filament\Pages\MyCalendar;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\AdminUsers\UserResource;
    use App\Filament\Resources\Departments\DepartmentResource;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;

    $page = $departmentsPage;
    $departments = $page['departments'];
    $queryWithout = fn (array $replace = [], array $remove = []): array => collect(request()->query())
        ->except($remove)
        ->merge($replace)
        ->filter(fn ($value) => filled($value))
        ->all();
    $menuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => Workplace::getUrl()],
            ['label' => 'Сотрудники', 'icon' => 'users', 'url' => UserResource::getUrl('index')],
            ['label' => 'Отделы', 'icon' => 'building', 'url' => DepartmentResource::getUrl('index'), 'active' => true],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
        ],
        'HR' => [
            ['label' => 'Заявки сотрудников', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index')],
            ['label' => 'Структура', 'icon' => 'list', 'url' => '#department-structure'],
            ['label' => 'Отчёты', 'icon' => 'file', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
        ],
    ];
@endphp

@component('layouts.work', [
    'title' => 'Отделы',
    'subtitle' => 'Структура компании и подразделения',
    'user' => auth()->user(),
    'active' => 'departments',
    'showSidebar' => true,
    'appClass' => 'nik-work-app--departments',
])
    <main class="nik-department-list nik-work-desktop">
        <section class="nik-directory-head">
            <div>
                <span>HR пространство · Отделы</span>
                <h1>Отделы</h1>
                <p>Структура компании, руководители подразделений и распределение сотрудников.</p>
            </div>
            <form class="nik-directory-search" method="GET" action="{{ $page['urls']['index'] }}">
                @foreach (request()->except(['q', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <x-work.icon name="search" />
                <input name="q" value="{{ $page['query']['q'] }}" placeholder="Поиск отдела..." />
            </form>
            @if ($page['canCreate'])
                <a class="nik-directory-primary" href="{{ $page['urls']['create'] }}"><x-work.icon name="plus" /> Добавить отдел</a>
            @endif
        </section>

        <section class="nik-directory-kpis">
            @foreach ($page['kpis'] as $kpi)
                <article class="is-{{ $kpi['tone'] }} {{ ($kpi['soon'] ?? false) ? 'is-muted' : '' }}">
                    <span><x-work.icon :name="$kpi['icon']" /></span>
                    <div>
                        <small>{{ $kpi['label'] }}</small>
                        <strong>{{ $kpi['value'] }}</strong>
                        <em>{{ ($kpi['soon'] ?? false) ? 'Скоро' : 'актуально' }}</em>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="nik-directory-layout">
            <div class="nik-directory-panel">
                <nav class="nik-directory-tabs" aria-label="Режим отделов">
                    <a class="{{ $page['view'] === 'cards' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['view' => 'cards'])) }}">Карточки</a>
                    <a class="{{ $page['view'] === 'list' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['view' => 'list'])) }}">Список</a>
                    <a class="{{ $page['view'] === 'structure' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?'.http_build_query($queryWithout(['view' => 'structure'])) }}">Структура</a>
                </nav>

                @if ($page['view'] === 'structure')
                    <div id="department-structure" class="nik-directory-structure">
                        <h2>Структура компании</h2>
                        @foreach ($page['allDepartments']->whereNull('parent_id') as $root)
                            <article>
                                <strong>{{ $root->name }}</strong>
                                <span>{{ $root->getActiveHead()?->name ?: 'Руководитель не указан' }} · {{ $root->employees_count }} сотрудников</span>
                                <div>
                                    @foreach ($page['allDepartments']->where('parent_id', $root->id) as $child)
                                        <a href="{{ DepartmentResource::getUrl('view', ['record' => $child]) }}">{{ $child->name }} <em>{{ $child->employees_count }}</em></a>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @elseif ($page['view'] === 'list')
                    <div class="nik-directory-table nik-directory-table--departments">
                        <div class="nik-directory-table-head">
                            <span>Название</span><span>Код</span><span>Родитель</span><span>Руководитель</span><span>ВРИО</span><span>Сотрудники</span><span>Статус</span>
                        </div>
                        @forelse ($departments as $department)
                            <a class="nik-directory-row" href="{{ DepartmentResource::getUrl('view', ['record' => $department]) }}">
                                <span><strong>{{ $department->name }}</strong><small>{{ $department->description ?: 'Описание не указано' }}</small></span>
                                <span>{{ $department->code ?: '—' }}</span>
                                <span>{{ $department->parent?->name ?: '—' }}</span>
                                <span>{{ $department->manager?->name ?: '—' }}</span>
                                <span>{{ $department->actingManager?->name ?: '—' }}</span>
                                <span>{{ $department->employees_count }}</span>
                                <span><x-work.badge :tone="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Активен' : 'Неактивен' }}</x-work.badge></span>
                            </a>
                        @empty
                            <div class="nik-directory-empty">Отделы не найдены.</div>
                        @endforelse
                    </div>
                @else
                    <div class="nik-department-card-grid">
                        @forelse ($departments as $department)
                            <article class="nik-department-card">
                                <a href="{{ DepartmentResource::getUrl('view', ['record' => $department]) }}" class="nik-department-card-main">
                                    <span><x-work.icon name="building" /></span>
                                    <div>
                                        <strong>{{ $department->name }}</strong>
                                        <small>{{ $department->code ?: 'Код не указан' }}</small>
                                    </div>
                                    <x-work.badge :tone="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Активен' : 'Неактивен' }}</x-work.badge>
                                </a>
                                <dl>
                                    <div><dt>Руководитель</dt><dd>{{ $department->manager?->name ?: 'Не указан' }}</dd></div>
                                    <div><dt>ВРИО</dt><dd>{{ $department->actingManager?->name ?: '—' }}</dd></div>
                                    <div><dt>Родительский отдел</dt><dd>{{ $department->parent?->name ?: '—' }}</dd></div>
                                    <div><dt>Сотрудники</dt><dd>{{ $department->employees_count }}</dd></div>
                                </dl>
                                <div>
                                    <a href="{{ DepartmentResource::getUrl('view', ['record' => $department]) }}">Открыть</a>
                                    <a href="{{ $page['urls']['employees'].'?department='.$department->id }}">Сотрудники</a>
                                    @if (DepartmentResource::canEdit($department))
                                        <a href="{{ DepartmentResource::getUrl('edit', ['record' => $department]) }}">Редактировать</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="nik-directory-empty">Отделы не найдены.</div>
                        @endforelse
                    </div>
                @endif
                <div class="nik-directory-pagination">{{ $departments->links() }}</div>
            </div>

            <aside class="nik-directory-side">
                <section>
                    <h2>Быстрые действия</h2>
                    <div class="nik-directory-quick">
                        @if ($page['canCreate'])
                            <a href="{{ $page['urls']['create'] }}"><x-work.icon name="plus" /> Добавить отдел</a>
                        @endif
                        <span><x-work.icon name="archive" /> Импорт структуры <em>Скоро</em></span>
                        <span><x-work.icon name="file" /> Экспорт структуры <em>Скоро</em></span>
                    </div>
                </section>
                <section>
                    <h2>Структура компании</h2>
                    <div class="nik-directory-mini-list">
                        @foreach ($page['allDepartments']->take(8) as $department)
                            <a href="{{ DepartmentResource::getUrl('view', ['record' => $department]) }}">
                                <span class="nik-directory-avatar is-small"><i>{{ mb_substr($department->name, 0, 1) }}</i></span>
                                <strong>{{ $department->name }}</strong>
                                <em>{{ $department->employees_count }} сотрудников</em>
                            </a>
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>
    </main>

    <div class="nik-work-mobile nik-directory-mobile nik-department-list-mobile" x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }">
        <header class="nik-directory-mobile-top">
            <a href="{{ Workplace::getUrl() }}"><img src="{{ asset('images/logo-icon.png') }}" alt="Никтрейд"><strong>Отделы</strong></a>
            <div><button><x-work.icon name="search" /></button><button><x-work.icon name="bell" /></button></div>
        </header>
        <h1>Отделы</h1>
        <section class="nik-directory-mobile-kpis">
            @foreach (array_slice($page['kpis'], 0, 4) as $kpi)
                <article><small>{{ $kpi['label'] }}</small><strong>{{ $kpi['value'] }}</strong><em>{{ ($kpi['soon'] ?? false) ? 'Скоро' : 'актуально' }}</em></article>
            @endforeach
        </section>
        <nav class="nik-directory-mobile-tabs">
            <a class="{{ $page['view'] === 'cards' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?view=cards' }}">Карточки</a>
            <a class="{{ $page['view'] === 'list' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?view=list' }}">Список</a>
            <a class="{{ $page['view'] === 'structure' ? 'is-active' : '' }}" href="{{ $page['urls']['index'].'?view=structure' }}">Структура</a>
        </nav>
        <form class="nik-directory-mobile-search" method="GET" action="{{ $page['urls']['index'] }}">
            <input name="q" value="{{ $page['query']['q'] }}" placeholder="Поиск отдела..." />
            <button type="submit"><x-work.icon name="search" /></button>
        </form>
        <section class="nik-directory-mobile-list">
            @forelse ($departments as $department)
                <a href="{{ DepartmentResource::getUrl('view', ['record' => $department]) }}" class="nik-directory-mobile-card">
                    <span class="nik-directory-avatar"><i>{{ mb_substr($department->name, 0, 1) }}</i></span>
                    <div><strong>{{ $department->name }}</strong><small>{{ $department->getActiveHead()?->name ?: 'Руководитель не указан' }} · {{ $department->employees_count }} сотрудников</small></div>
                    <x-work.badge :tone="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Активен' : 'Неактивен' }}</x-work.badge>
                </a>
            @empty
                <div class="nik-directory-empty">Отделы не найдены.</div>
            @endforelse
        </section>
        <x-work.mobile-bottom-sheets :menu-groups="$menuGroups" :requests-url="\App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')" />
        <x-work.mobile-bottom-nav />
    </div>
@endcomponent
