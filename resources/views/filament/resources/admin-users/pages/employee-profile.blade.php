@php
    use App\Models\EmployeeScheduleRequest;
    use App\Models\User;

    $employee = $profile['employee'];
    $documents = $profile['documents'];
    $requestCounts = $profile['requestCounts'];
    $manager = $profile['manager'];

    $initials = collect(explode(' ', trim($employee->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->join('');

    $value = fn ($state): string => filled($state) ? (string) $state : 'Не указано';
    $dateValue = fn ($date): string => $date ? $date->format('d.m.Y') : 'Не указано';
    $birthDateValue = $employee->date_of_birth
        ? $employee->date_of_birth->format('d.m.Y').' ('.$employee->age_label.')'
        : $dateValue(null);
    $statusTone = match ($profile['statusColor']) {
        'success' => 'green',
        'danger' => 'red',
        'warning' => 'amber',
        default => 'gray',
    };
    $requestTone = fn (?string $status): string => match ($status) {
        EmployeeScheduleRequest::STATUS_APPROVED => 'green',
        EmployeeScheduleRequest::STATUS_REJECTED => 'red',
        EmployeeScheduleRequest::STATUS_RETURNED => 'amber',
        default => 'gray',
    };

    $tabs = [
        ['label' => 'Обзор', 'href' => '#employee-profile-about', 'active' => true],
        ['label' => 'Деятельность', 'href' => '#employee-profile-activity', 'disabled' => true],
        ['label' => 'Задачи', 'href' => '#employee-profile-tasks', 'disabled' => true],
        ['label' => 'Документы', 'href' => '#employee-profile-documents', 'enabled' => $profile['canViewDocuments']],
        ['label' => 'График', 'href' => '#employee-profile-schedule', 'enabled' => $profile['canViewSchedule']],
        ['label' => 'Заявки', 'href' => '#employee-profile-requests', 'enabled' => $profile['canViewSchedule']],
        ['label' => 'Доступы', 'href' => '#employee-profile-access', 'enabled' => $profile['canViewAccess']],
        ['label' => 'История', 'href' => '#employee-profile-history', 'disabled' => true],
    ];

    $quickCards = [
        ['label' => 'Данные', 'icon' => 'users', 'href' => '#employee-profile-about-mobile'],
        ['label' => 'Активность', 'icon' => 'zap', 'href' => '#employee-profile-activity', 'disabled' => true],
        ['label' => 'Заявки', 'icon' => 'link', 'href' => '#employee-profile-requests-mobile', 'disabled' => ! $profile['canViewSchedule']],
        ['label' => 'Задачи', 'icon' => 'check-square', 'href' => '#employee-profile-tasks', 'disabled' => true],
        ['label' => 'Документы', 'icon' => 'file', 'href' => '#employee-profile-documents-mobile', 'disabled' => ! $profile['canViewDocuments']],
        ['label' => 'График', 'icon' => 'calendar', 'href' => '#employee-profile-schedule-mobile', 'disabled' => ! $profile['canViewSchedule']],
    ];
@endphp

@component('layouts.work', [
    'title' => $employee->name,
    'subtitle' => trim($value($employee->position).' · '.$value($employee->department?->name), ' ·'),
    'user' => auth()->user(),
    'active' => 'employees',
    'showSidebar' => true,
    'appClass' => 'nik-work-app--employee nik-work-app--employee-profile',
])
    <main class="nik-employee-profile nik-work-desktop">
        <div class="nik-employee-profile-top">
            <a class="nik-employee-profile-back" href="{{ $profile['listUrl'] }}">
                <x-work.icon name="chevron-left" />
                <span>Сотрудники</span>
            </a>

            <div class="nik-employee-profile-heading">
                <div>
                    <h1>{{ $employee->name }}</h1>
                    <p>{{ $value($employee->position) }} · {{ $value($employee->department?->name) }}</p>
                </div>
                <x-work.badge :tone="$statusTone">{{ $profile['statusLabel'] }}</x-work.badge>
            </div>

            <div class="nik-employee-profile-actions">
                <button type="button" class="is-disabled" disabled>
                    <x-work.icon name="message" />
                    <span>Написать сообщение</span>
                    <em>Скоро</em>
                </button>
                @if ($profile['canEdit'])
                    <a href="{{ $profile['editUrl'] }}">
                        <x-work.icon name="file" />
                        <span>Редактировать</span>
                    </a>
                @endif
                <button type="button" class="nik-employee-profile-more" disabled aria-label="Дополнительные действия">
                    <x-work.icon name="grid" />
                </button>
            </div>
        </div>

        <section class="nik-employee-profile-grid">
            <article class="nik-employee-glass-card nik-employee-profile-hero">
                <div class="nik-employee-profile-photo">
                    @if ($profile['photoUrl'])
                        <img src="{{ $profile['photoUrl'] }}" alt="Фото сотрудника {{ $employee->name }}">
                    @else
                        <span>{{ $initials ?: 'N' }}</span>
                    @endif
                </div>

                <div class="nik-employee-profile-info">
                    <div class="nik-employee-profile-info-head">
                        <div>
                            <span>Личное дело</span>
                            <strong>{{ $employee->name }}</strong>
                        </div>
                        <x-work.badge :tone="$statusTone">{{ $profile['statusLabel'] }}</x-work.badge>
                    </div>

                    <dl class="nik-employee-profile-fields">
                        <div><dt>Email</dt><dd>{{ $value($employee->email) }}</dd></div>
                        <div><dt>Дата рождения</dt><dd>{{ $birthDateValue }}</dd></div>
                        <div><dt>Телефон</dt><dd>{{ $value($employee->phone) }}</dd></div>
                        <div><dt>Дата найма</dt><dd>{{ $dateValue($employee->hire_date) }}</dd></div>
                        <div><dt>Должность</dt><dd>{{ $value($employee->position) }}</dd></div>
                        <div><dt>График работы</dt><dd>{{ $employee->getScheduleTypeLabel() }}</dd></div>
                        <div><dt>Отдел</dt><dd>{{ $value($employee->department?->name) }}</dd></div>
                        <div><dt>Руководитель</dt><dd>{{ $value($manager?->name) }}</dd></div>
                        <div><dt>Основная роль</dt><dd>{{ $profile['roleLabel'] }}</dd></div>
                        <div><dt>Тип трудоустройства</dt><dd>{{ $employee->getEmploymentTypeLabel() }}</dd></div>
                    </dl>
                </div>
            </article>

            <aside class="nik-employee-glass-card nik-employee-profile-summary">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Ключевые показатели</span>
                        <strong>Кадровый статус</strong>
                    </div>
                    <x-work.badge tone="gray">Сейчас</x-work.badge>
                </div>

                <div class="nik-employee-summary-list">
                    <div>
                        <span><x-work.icon name="file" /></span>
                        <p>Комплектность документов</p>
                        <strong>{{ $profile['canViewDocuments'] ? $documents['completeness'].'%' : 'Нет доступа' }}</strong>
                    </div>
                    <div>
                        <span><x-work.icon name="alert" /></span>
                        <p>Не хватает документов</p>
                        <strong>{{ $profile['canViewDocuments'] ? $documents['missingCount'] : '—' }}</strong>
                    </div>
                    <div>
                        <span><x-work.icon name="calendar" /></span>
                        <p>График сегодня</p>
                        <strong>{{ $profile['canViewSchedule'] ? $profile['todayStatus'] : 'Нет доступа' }}</strong>
                    </div>
                    <div>
                        <span><x-work.icon name="link" /></span>
                        <p>Активные заявки</p>
                        <strong>{{ $profile['canViewSchedule'] ? $requestCounts['pending'] : '—' }}</strong>
                    </div>
                </div>
            </aside>

            <nav class="nik-employee-profile-tabs" aria-label="Разделы профиля">
                @foreach ($tabs as $tab)
                    @if (($tab['disabled'] ?? false) || ! ($tab['enabled'] ?? true))
                        <span>
                            {{ $tab['label'] }}
                            <em>Скоро</em>
                        </span>
                    @else
                        <a href="{{ $tab['href'] }}" class="{{ ($tab['active'] ?? false) ? 'is-active' : '' }}">{{ $tab['label'] }}</a>
                    @endif
                @endforeach
            </nav>

            <article id="employee-profile-about" class="nik-employee-glass-card nik-employee-profile-card">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Обзор</span>
                        <strong>О сотруднике</strong>
                    </div>
                </div>
                <p class="nik-employee-profile-copy">
                    {{ $employee->name }} — {{ mb_strtolower($value($employee->position)) }}.
                    Отдел: {{ $value($employee->department?->name) }}.
                    Руководитель: {{ $value($manager?->name) }}.
                </p>
                <div class="nik-employee-profile-chips">
                    <x-work.badge>{{ $profile['roleLabel'] }}</x-work.badge>
                    <x-work.badge :tone="$statusTone">{{ $profile['statusLabel'] }}</x-work.badge>
                    <x-work.badge tone="gray">{{ $employee->getEmploymentTypeLabel() }}</x-work.badge>
                </div>
            </article>

            <article id="employee-profile-activity" class="nik-employee-glass-card nik-employee-profile-card">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Деятельность</span>
                        <strong>Активность</strong>
                    </div>
                    <x-work.badge tone="gray">Скоро</x-work.badge>
                </div>
                <div class="nik-employee-disabled-state">История активности будет подключена позже.</div>
            </article>

            <article id="employee-profile-schedule" class="nik-employee-glass-card nik-employee-profile-card">
                <div class="nik-employee-card-head">
                    <div>
                        <span>{{ $profile['monthLabel'] }}</span>
                        <strong>График работы</strong>
                    </div>
                    @if ($profile['canViewSchedule'])
                        <a href="{{ $profile['calendarUrl'] }}">Открыть</a>
                    @else
                        <x-work.badge tone="gray">Нет доступа</x-work.badge>
                    @endif
                </div>

                @if ($profile['canViewSchedule'])
                    <x-work.calendar-mini
                        :month-label="$profile['monthLabel']"
                        :days="$profile['calendarDays']"
                        :url="$profile['calendarUrl']"
                    />
                    <div class="nik-employee-profile-today">
                        <strong>{{ $profile['shiftLabel'] }}</strong>
                        <span>{{ $profile['todayStatus'] }}</span>
                    </div>
                @else
                    <div class="nik-employee-disabled-state">График недоступен для текущего пользователя.</div>
                @endif
            </article>

            <article id="employee-profile-requests" class="nik-employee-glass-card nik-employee-profile-card nik-employee-profile-wide">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Последние обращения</span>
                        <strong>Заявки</strong>
                    </div>
                    @if ($profile['canViewSchedule'])
                        <a href="{{ $profile['requestsUrl'] }}">Открыть все заявки</a>
                    @endif
                </div>
                <div class="nik-employee-profile-list">
                    @forelse ($profile['recentRequests'] as $request)
                        <div>
                            <div>
                                <strong>{{ $request->getTypeLabel() }}</strong>
                                <small>{{ $request->getDateRangeLabel() }}</small>
                            </div>
                            <x-work.badge :tone="$requestTone($request->status)">{{ $request->getStatusLabel() }}</x-work.badge>
                        </div>
                    @empty
                        <div class="nik-employee-disabled-state">Заявок пока нет.</div>
                    @endforelse
                </div>
            </article>

            <article id="employee-profile-documents" class="nik-employee-glass-card nik-employee-profile-card nik-employee-profile-wide">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Кадровый комплект</span>
                        <strong>Документы</strong>
                    </div>
                    @if ($profile['canViewDocuments'])
                        <a href="#employee-profile-documents">Открыть документы</a>
                    @else
                        <x-work.badge tone="gray">Нет доступа</x-work.badge>
                    @endif
                </div>

                @if ($profile['canViewDocuments'])
                    <div class="nik-employee-doc-progress">
                        <div><span>Комплектность</span><strong>{{ $documents['completeness'] }}%</strong></div>
                        <i><b style="width: {{ $documents['completeness'] }}%;"></b></i>
                        <p>Не хватает: {{ $documents['missingCount'] }}. Истекают: {{ $documents['expiringCount'] }}. Просрочены: {{ $documents['expiredCount'] }}.</p>
                    </div>

                    <div class="nik-employee-profile-list">
                        @forelse ($documents['items'] as $document)
                            <div>
                                <div>
                                    <strong>{{ $document->getCategoryLabel() }}</strong>
                                    <small>{{ $document->expires_at ? 'до '.$document->expires_at->format('d.m.Y') : 'Без срока действия' }}</small>
                                </div>
                                <x-work.badge :tone="$document->isExpired() ? 'red' : ($document->expiresSoon() ? 'amber' : 'green')">{{ $document->getExpirationLabel() }}</x-work.badge>
                            </div>
                        @empty
                            <div class="nik-employee-disabled-state">Документы пока не загружены. Не хватает: {{ $documents['missingCount'] }}.</div>
                        @endforelse
                    </div>
                @else
                    <div class="nik-employee-disabled-state">Документы недоступны для текущего пользователя.</div>
                @endif
            </article>

            <article id="employee-profile-tasks" class="nik-employee-glass-card nik-employee-profile-card">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Поручения</span>
                        <strong>Задачи</strong>
                    </div>
                    <x-work.badge tone="gray">Скоро</x-work.badge>
                </div>
                <div class="nik-employee-disabled-state">Модуль задач будет подключён позже.</div>
            </article>

            <article id="employee-profile-access" class="nik-employee-glass-card nik-employee-profile-card">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Роли и доступ</span>
                        <strong>Доступы</strong>
                    </div>
                    <x-work.badge tone="gray">{{ $profile['canViewAccess'] ? 'Обзор' : 'Скоро' }}</x-work.badge>
                </div>
                @if ($profile['canViewAccess'])
                    <div class="nik-employee-profile-list is-compact">
                        <div><span>Основная роль</span><strong>{{ $profile['roleLabel'] }}</strong></div>
                        <div><span>Отдел</span><strong>{{ $value($employee->department?->name) }}</strong></div>
                        <div><span>Руководитель</span><strong>{{ $value($manager?->name) }}</strong></div>
                    </div>
                @else
                    <div class="nik-employee-disabled-state">Управление доступами будет доступно позже.</div>
                @endif
            </article>

            <article id="employee-profile-history" class="nik-employee-glass-card nik-employee-profile-card nik-employee-profile-wide">
                <div class="nik-employee-card-head">
                    <div>
                        <span>Журнал</span>
                        <strong>История</strong>
                    </div>
                    <x-work.badge tone="gray">Скоро</x-work.badge>
                </div>
                <div class="nik-employee-disabled-state">История изменений будет подключена позже.</div>
            </article>
        </section>
    </main>

    <div
        class="nik-work-mobile nik-employee-profile-mobile"
        aria-label="Мобильный профиль сотрудника"
        x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
        x-on:keydown.escape.window="closeSheet()"
    >
        <header class="nik-work-mobile-top">
            <a href="{{ $profile['workplaceUrl'] }}" class="nik-work-mobile-brand" aria-label="Никтрейд">
                <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
            </a>
            <div class="nik-work-mobile-top-actions">
                <button type="button" class="nik-work-mobile-icon-button" aria-label="Поиск"><x-work.icon name="search" /></button>
                <button type="button" class="nik-work-mobile-icon-button has-badge" aria-label="Уведомления"><x-work.icon name="bell" /><span>3</span></button>
                <x-work.user-menu :user="auth()->user()" button-class="nik-work-mobile-avatar" :show-chevron="false" />
            </div>
        </header>

        <a class="nik-employee-mobile-back" href="{{ $profile['listUrl'] }}">
            <x-work.icon name="chevron-left" />
            <span>Сотрудники</span>
        </a>

        <section class="nik-work-mobile-card nik-employee-mobile-hero">
            <div class="nik-employee-mobile-photo">
                @if ($profile['photoUrl'])
                    <img src="{{ $profile['photoUrl'] }}" alt="Фото сотрудника {{ $employee->name }}">
                @else
                    <span>{{ $initials ?: 'N' }}</span>
                @endif
            </div>
            <x-work.badge :tone="$statusTone">{{ $profile['statusLabel'] }}</x-work.badge>
            <h1>{{ $employee->name }}</h1>
            <p>{{ $value($employee->position) }}<br>{{ $value($employee->department?->name) }}</p>
            <div class="nik-employee-mobile-actions">
                <button type="button" disabled><x-work.icon name="message" /><span>Написать сообщение</span><em>Скоро</em></button>
                @if ($profile['canEdit'])
                    <a href="{{ $profile['editUrl'] }}" aria-label="Редактировать"><x-work.icon name="file" /></a>
                @endif
            </div>
        </section>

        <main class="nik-work-mobile-stack">
            <section class="nik-employee-mobile-quick">
                @foreach ($quickCards as $card)
                    @if ($card['disabled'] ?? false)
                        <span>
                            <x-work.icon :name="$card['icon']" />
                            <strong>{{ $card['label'] }}</strong>
                            <em>Скоро</em>
                        </span>
                    @else
                        <a href="{{ $card['href'] }}">
                            <x-work.icon :name="$card['icon']" />
                            <strong>{{ $card['label'] }}</strong>
                        </a>
                    @endif
                @endforeach
            </section>

            <section id="employee-profile-documents-mobile" class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-head">
                    <div class="nik-work-mobile-card-title"><span><x-work.icon name="alert" /></span><strong>Ключевые показатели</strong></div>
                    <small>Сейчас</small>
                </div>
                <div class="nik-employee-mobile-stats">
                    <div><span>Документы</span><strong>{{ $profile['canViewDocuments'] ? $documents['completeness'].'%' : 'Нет доступа' }}</strong></div>
                    <div><span>Не хватает</span><strong>{{ $profile['canViewDocuments'] ? $documents['missingCount'] : '—' }}</strong></div>
                    <div><span>Заявки</span><strong>{{ $profile['canViewSchedule'] ? $requestCounts['pending'] : '—' }}</strong></div>
                    <div><span>График</span><strong>{{ $profile['canViewSchedule'] ? $profile['todayStatus'] : 'Нет доступа' }}</strong></div>
                </div>
            </section>

            <section id="employee-profile-about-mobile" class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-title"><span><x-work.icon name="users" /></span><strong>О сотруднике</strong></div>
                <div class="nik-employee-mobile-facts">
                    <div><span>Email</span><strong>{{ $value($employee->email) }}</strong></div>
                    <div><span>Телефон</span><strong>{{ $value($employee->phone) }}</strong></div>
                    <div><span>Дата рождения</span><strong>{{ $birthDateValue }}</strong></div>
                    <div><span>Дата найма</span><strong>{{ $dateValue($employee->hire_date) }}</strong></div>
                    <div><span>Руководитель</span><strong>{{ $value($manager?->name) }}</strong></div>
                    <div><span>Роль</span><strong>{{ $profile['roleLabel'] }}</strong></div>
                </div>
            </section>

            <section id="employee-profile-schedule-mobile" class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-head">
                    <div class="nik-work-mobile-card-title"><span><x-work.icon name="file" /></span><strong>Документы</strong></div>
                    <strong class="nik-work-mobile-percent">{{ $profile['canViewDocuments'] ? $documents['completeness'].'%' : '—' }}</strong>
                </div>
                @if ($profile['canViewDocuments'])
                    <div class="nik-work-mobile-doc-summary">
                        <span>Комплектность документов</span>
                        <div class="nik-work-mobile-progress"><i style="width: {{ $documents['completeness'] }}%;"></i></div>
                        <p>Не хватает: {{ $documents['missingCount'] }}. Истекают: {{ $documents['expiringCount'] }}. Просрочены: {{ $documents['expiredCount'] }}.</p>
                    </div>
                @else
                    <div class="nik-work-mobile-empty">Документы недоступны для текущего пользователя.</div>
                @endif
            </section>

            <section id="employee-profile-requests-mobile" class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-head">
                    <div class="nik-work-mobile-card-title"><span><x-work.icon name="calendar" /></span><strong>График</strong></div>
                    @if ($profile['canViewSchedule'])
                        <a href="{{ $profile['calendarUrl'] }}">Открыть</a>
                    @endif
                </div>
                @if ($profile['canViewSchedule'])
                    <x-work.calendar-mini :month-label="$profile['monthLabel']" :days="$profile['calendarDays']" :url="$profile['calendarUrl']" />
                    <div class="nik-work-mobile-empty">{{ $profile['shiftLabel'] }} · {{ $profile['todayStatus'] }}</div>
                @else
                    <div class="nik-work-mobile-empty">График недоступен для текущего пользователя.</div>
                @endif
            </section>

            <section id="employee-profile-tasks-mobile" class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-head">
                    <div class="nik-work-mobile-card-title"><span><x-work.icon name="link" /></span><strong>Заявки</strong></div>
                    @if ($profile['canViewSchedule'])
                        <a href="{{ $profile['requestsUrl'] }}">Все</a>
                    @endif
                </div>
                <div class="nik-work-mobile-list">
                    @forelse ($profile['recentRequests'] as $request)
                        <div class="nik-work-mobile-row">
                            <div><strong>{{ $request->getTypeLabel() }}</strong><small>{{ $request->getDateRangeLabel() }}</small></div>
                            <span>{{ $request->getStatusLabel() }}</span>
                            <i>›</i>
                        </div>
                    @empty
                        <div class="nik-work-mobile-empty">Заявок пока нет.</div>
                    @endforelse
                </div>
            </section>

            <section class="nik-work-mobile-card">
                <div class="nik-work-mobile-card-head">
                    <div class="nik-work-mobile-card-title"><span><x-work.icon name="check-square" /></span><strong>Задачи</strong></div>
                    <small>Скоро</small>
                </div>
                <div class="nik-work-mobile-empty">Модуль задач будет подключён позже.</div>
            </section>
        </main>

        <x-work.mobile-bottom-sheets
            :menu-groups="$profile['mobileMenuGroups']"
            :request-counts="$requestCounts"
            :recent-requests="$profile['recentRequests']"
            :create-request-url="$profile['createRequestUrl']"
            :requests-url="$profile['requestsUrl']"
        />
        <x-work.mobile-bottom-nav />
    </div>
@endcomponent
