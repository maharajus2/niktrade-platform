@php
    use App\Filament\Pages\MyCalendar;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\AdminUsers\UserResource;
    use App\Filament\Resources\Departments\DepartmentResource;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
    use App\Models\EmployeeScheduleRequest;

    $scheduleRequest = $request;
    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->join('')
        : 'N';

    $statusTone = match ($scheduleRequest->status) {
        EmployeeScheduleRequest::STATUS_APPROVED => 'green',
        EmployeeScheduleRequest::STATUS_REJECTED => 'red',
        EmployeeScheduleRequest::STATUS_RETURNED => 'amber',
        EmployeeScheduleRequest::STATUS_CANCELLED => 'gray',
        EmployeeScheduleRequest::STATUS_IN_REVIEW,
        EmployeeScheduleRequest::STATUS_FORWARDED => 'blue',
        default => 'amber',
    };
    $lifecycleLabel = match (true) {
        $scheduleRequest->isDeletedState() => 'Удалённые',
        $scheduleRequest->isArchived() => 'Архив',
        default => 'Активная',
    };
    $timeLabel = $scheduleRequest->is_all_day
        ? 'Весь день'
        : collect([
            $scheduleRequest->starts_at ? substr((string) $scheduleRequest->starts_at, 0, 5) : null,
            $scheduleRequest->ends_at ? substr((string) $scheduleRequest->ends_at, 0, 5) : null,
        ])->filter()->join(' — ');
    $workflow = $scheduleRequest->approvalWorkflow;
    $events = $workflow?->events?->sortBy('created_at') ?? collect();
    $createdAt = $scheduleRequest->created_at?->format('d.m.Y H:i') ?? '—';
    $reviewedAt = $scheduleRequest->reviewed_at?->format('d.m.Y H:i') ?? '—';

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => Workplace::getUrl()],
            ['label' => 'Календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
            ['label' => 'Заявки', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index'), 'active' => true],
        ],
        'Заявки' => [
            ['label' => 'Создать заявку', 'icon' => 'plus', 'url' => EmployeeScheduleRequestResource::getUrl('create')],
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
        'title' => $scheduleRequest->getTypeLabel(),
        'subtitle' => $scheduleRequest->employee?->name.' · '.$scheduleRequest->getStatusLabel().' · '.$scheduleRequest->getDateRangeLabel(),
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
                    <span class="nik-schedule-request-eyebrow">Карточка заявки</span>
                    <h1>{{ $scheduleRequest->getTypeLabel() }}</h1>
                    <p>{{ $scheduleRequest->employee?->name ?? 'Сотрудник не указан' }} · {{ $scheduleRequest->getDateRangeLabel() }} · {{ $timeLabel ?: 'Время не указано' }}</p>
                </div>

                <div class="nik-schedule-request-badges">
                    <span class="nik-schedule-request-badge is-{{ $statusTone }}">{{ $scheduleRequest->getStatusLabel() }}</span>
                    <span class="nik-schedule-request-badge">{{ $lifecycleLabel }}</span>
                    @if ($scheduleRequest->getReasonLabel())
                        <span class="nik-schedule-request-badge">{{ $scheduleRequest->getReasonLabel() }}</span>
                    @endif
                </div>
            </section>

            <main class="nik-schedule-request-layout">
                <div class="nik-schedule-request-main">
                    <section class="nik-schedule-request-card">
                        <div class="nik-schedule-request-section-head">
                            <div>
                                <span>Основное</span>
                                <h2>Данные заявки</h2>
                            </div>
                        </div>

                        <div class="nik-schedule-request-facts">
                            <div><small>Сотрудник</small><strong>{{ $scheduleRequest->employee?->name ?? '—' }}</strong></div>
                            <div><small>Отдел</small><strong>{{ $scheduleRequest->employee?->department?->name ?? '—' }}</strong></div>
                            <div><small>Тип</small><strong>{{ $scheduleRequest->getTypeLabel() }}</strong></div>
                            <div><small>Период</small><strong>{{ $scheduleRequest->getDateRangeLabel() }}</strong></div>
                            <div><small>Время</small><strong>{{ $timeLabel ?: '—' }}</strong></div>
                            <div><small>Создана</small><strong>{{ $createdAt }}</strong></div>
                            <div><small>Автор</small><strong>{{ $scheduleRequest->requestedBy?->name ?? '—' }}</strong></div>
                            <div><small>Согласующий</small><strong>{{ $workflow?->currentApprover?->name ?? $scheduleRequest->employee?->getEffectiveManager()?->name ?? '—' }}</strong></div>
                        </div>

                        @if (filled($scheduleRequest->reason) || filled($scheduleRequest->manager_comment))
                            <div class="nik-schedule-request-comments">
                                @if (filled($scheduleRequest->reason))
                                    <div><small>Комментарий сотрудника</small><p>{{ $scheduleRequest->reason }}</p></div>
                                @endif
                                @if (filled($scheduleRequest->manager_comment))
                                    <div><small>Комментарий руководителя</small><p>{{ $scheduleRequest->manager_comment }}</p></div>
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="nik-schedule-request-card">
                        <div class="nik-schedule-request-section-head">
                            <div>
                                <span>Маршрут</span>
                                <h2>История заявки</h2>
                            </div>
                        </div>

                        <div class="nik-schedule-request-timeline">
                            @forelse ($events as $event)
                                <div>
                                    <span><x-work.icon name="check-circle" /></span>
                                    <div>
                                        <strong>{{ $event->getActionLabel() }}</strong>
                                        <small>{{ $event->created_at?->format('d.m.Y H:i') }} · {{ $event->actor?->name ?? 'Система' }}</small>
                                        @if ($event->forwardedTo)
                                            <p>Передано: {{ $event->forwardedTo->name }}</p>
                                        @endif
                                        @if (filled($event->comment))
                                            <p>{{ $event->comment }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="nik-schedule-request-empty">История заявки пока пуста.</div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="nik-schedule-request-side">
                    <section class="nik-schedule-request-card">
                        <div class="nik-schedule-request-section-head">
                            <div>
                                <span>Состояние</span>
                                <h2>{{ $scheduleRequest->getStatusLabel() }}</h2>
                            </div>
                        </div>
                        <div class="nik-schedule-request-status-card is-{{ $statusTone }}">
                            <strong>{{ $scheduleRequest->getStatusLabel() }}</strong>
                            <span>Создано событий: {{ $scheduleRequest->created_schedule_entries_count ?? 0 }}</span>
                            <span>Рассмотрена: {{ $reviewedAt }}</span>
                        </div>
                    </section>

                    <section class="nik-schedule-request-card">
                        <div class="nik-schedule-request-section-head">
                            <div>
                                <span>Действия</span>
                                <h2>Быстро</h2>
                            </div>
                        </div>
                        <div class="nik-schedule-request-quick">
                            <a href="{{ EmployeeScheduleRequestResource::getUrl('create') }}"><x-work.icon name="plus" /><span>Создать заявку</span></a>
                            <a href="{{ EmployeeScheduleRequestResource::getUrl('index') }}"><x-work.icon name="link" /><span>Все заявки</span></a>
                            <a href="{{ MyCalendar::getUrl() }}"><x-work.icon name="calendar" /><span>Календарь</span></a>
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
