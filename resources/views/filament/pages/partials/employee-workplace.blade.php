@php
    $employee = $workspace['employee'];
    $documents = $workspace['documents'];
    $requestCounts = $workspace['requestCounts'];
    $attentionItems = $workspace['attentionItems'];
    $initials = collect(explode(' ', trim($employee->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->join('');
@endphp

<style>
    .ew-shell,
    .ew-shell * {
        box-sizing: border-box;
    }

    .ew-shell {
        width: 100%;
        max-width: 1640px;
        margin: -1.25rem auto;
        padding: 28px 32px 36px;
        border-radius: 32px;
        background:
            radial-gradient(circle at 18% 0%, rgba(37, 99, 235, .12), transparent 26rem),
            radial-gradient(circle at 88% 18%, rgba(14, 165, 233, .09), transparent 24rem),
            linear-gradient(135deg, #f8fbff 0%, #f3f7fc 48%, #f8fafc 100%);
        color: #0f172a;
    }

    .ew-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 24px;
        margin-bottom: 28px;
    }

    .ew-title {
        margin: 0;
        color: #0f172a;
        font-size: 30px;
        font-weight: 850;
        line-height: 1.1;
        letter-spacing: 0;
    }

    .ew-date {
        margin-top: 8px;
        color: #64748b;
        font-size: 15px;
        font-weight: 600;
    }

    .ew-tools {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ew-search {
        display: flex;
        width: min(330px, 32vw);
        height: 48px;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 14px;
        background: rgba(255, 255, 255, .9);
        padding: 0 16px;
        color: #94a3b8;
        font-size: 14px;
        font-weight: 650;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .05);
    }

    .ew-icon-button,
    .ew-avatar {
        display: inline-flex;
        width: 48px;
        height: 48px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 14px;
        background: rgba(255, 255, 255, .95);
        color: #1e293b;
        font-weight: 850;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .05);
    }

    .ew-avatar {
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
    }

    .ew-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 24px;
        align-items: start;
    }

    .ew-span-3 { grid-column: span 3 / span 3; }
    .ew-span-4 { grid-column: span 4 / span 4; }
    .ew-span-5 { grid-column: span 5 / span 5; }
    .ew-span-6 { grid-column: span 6 / span 6; }
    .ew-span-8 { grid-column: span 8 / span 8; }

    .ew-stack {
        display: grid;
        gap: 20px;
    }

    .ew-card {
        overflow: hidden;
        border: 1px solid #e8edf5;
        border-radius: 24px;
        background: rgba(255, 255, 255, .96);
        padding: 24px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
    }

    .ew-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }

    .ew-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #0f172a;
        font-size: 17px;
        font-weight: 850;
    }

    .ew-card-subtitle {
        margin-top: 5px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .ew-symbol {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 15px;
        font-weight: 900;
    }

    .ew-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #eff6ff;
        padding: 7px 12px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 850;
        text-decoration: none;
        white-space: nowrap;
    }

    .ew-day-layout {
        display: grid;
        grid-template-columns: minmax(190px, .92fr) minmax(0, 1.35fr);
        gap: 28px;
    }

    .ew-shift-card {
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        padding: 18px;
    }

    .ew-shift-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 750;
    }

    .ew-shift-time {
        margin-top: 8px;
        color: #0f172a;
        font-size: 25px;
        font-weight: 900;
        line-height: 1.1;
    }

    .ew-status {
        display: inline-flex;
        margin-top: 14px;
        border-radius: 999px;
        background: #dcfce7;
        padding: 8px 12px;
        color: #15803d;
        font-size: 13px;
        font-weight: 850;
    }

    .ew-progress-wrap {
        margin-top: 26px;
        border-top: 1px solid #e8edf5;
        padding-top: 20px;
    }

    .ew-progress {
        height: 8px;
        overflow: hidden;
        border-radius: 999px;
        background: #e7edf5;
    }

    .ew-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #2563eb, #38bdf8);
    }

    .ew-timeline {
        position: relative;
        display: grid;
        gap: 18px;
        padding-left: 6px;
    }

    .ew-timeline::before {
        position: absolute;
        top: 12px;
        bottom: 12px;
        left: 72px;
        width: 2px;
        border-radius: 999px;
        background: #dbe3ef;
        content: "";
    }

    .ew-timeline-row {
        position: relative;
        display: grid;
        grid-template-columns: 56px 28px minmax(0, 1fr);
        align-items: start;
        gap: 14px;
        min-height: 42px;
    }

    .ew-timeline-time {
        color: #0f172a;
        font-size: 13px;
        font-weight: 850;
        line-height: 28px;
    }

    .ew-timeline-dot {
        position: relative;
        z-index: 1;
        display: inline-flex;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        margin-top: 5px;
        border: 4px solid #eef5ff;
        border-radius: 999px;
    }

    .ew-timeline-title {
        color: #0f172a;
        font-size: 14px;
        font-weight: 850;
    }

    .ew-timeline-meta {
        margin-top: 4px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .ew-calendar-head {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 8px;
    }

    .ew-calendar-weekday {
        color: #64748b;
        font-size: 13px;
        font-weight: 850;
        text-align: center;
    }

    .ew-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }

    .ew-day-cell {
        position: relative;
        display: flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 750;
    }

    .ew-day-cell.is-muted {
        color: #94a3b8;
    }

    .ew-day-cell.is-today {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .25);
    }

    .ew-event-dots {
        position: absolute;
        bottom: 4px;
        left: 50%;
        display: flex;
        gap: 3px;
        transform: translateX(-50%);
    }

    .ew-event-dot {
        width: 4px;
        height: 4px;
        border-radius: 999px;
    }

    .ew-alert-list,
    .ew-action-list,
    .ew-message-list,
    .ew-doc-list,
    .ew-request-list {
        display: grid;
        gap: 12px;
    }

    .ew-alert {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        border-radius: 16px;
        padding: 14px;
    }

    .ew-alert.is-amber { background: #fff7ed; }
    .ew-alert.is-red { background: #fff1f2; }
    .ew-alert.is-blue { background: #eff6ff; }

    .ew-alert-icon {
        display: inline-flex;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255, 255, 255, .78);
        font-weight: 900;
    }

    .ew-row-title {
        color: #0f172a;
        font-size: 14px;
        font-weight: 850;
    }

    .ew-row-meta {
        margin-top: 3px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .ew-action {
        display: grid;
        grid-template-columns: 32px minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        min-height: 48px;
        border: 1px solid #e5eaf2;
        border-radius: 13px;
        background: #fff;
        padding: 10px 12px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 850;
        text-decoration: none;
    }

    .ew-action.is-primary {
        border-color: #0ea5e9;
        background: #0ea5e9;
        color: #fff;
    }

    .ew-action.is-muted {
        background: #f8fafc;
        color: #94a3b8;
        cursor: default;
    }

    .ew-kanban {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .ew-kanban-column {
        min-height: 150px;
        border-radius: 16px;
        background: #f5f7fb;
        padding: 14px;
    }

    .ew-kanban-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 850;
    }

    .ew-task-placeholder {
        margin-top: 12px;
        border: 1px solid #e5eaf2;
        border-radius: 13px;
        background: rgba(255, 255, 255, .78);
        padding: 13px;
        color: #64748b;
        font-size: 13px;
        font-weight: 650;
    }

    .ew-request-row,
    .ew-doc-row,
    .ew-message-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 14px;
        border: 1px solid #e8edf5;
        border-radius: 14px;
        background: #fff;
        padding: 13px 16px;
    }

    .ew-status-chip {
        display: inline-flex;
        border-radius: 999px;
        background: #fef3c7;
        padding: 6px 10px;
        color: #b45309;
        font-size: 12px;
        font-weight: 850;
        white-space: nowrap;
    }

    .ew-status-chip.is-good {
        background: #dcfce7;
        color: #15803d;
    }

    .ew-status-chip.is-bad {
        background: #fee2e2;
        color: #dc2626;
    }

    .ew-designed-empty {
        border-radius: 18px;
        background: linear-gradient(135deg, #f1f5f9, #eff6ff);
        padding: 18px;
        color: #64748b;
        font-size: 14px;
        font-weight: 650;
    }

    @media (max-width: 1280px) {
        .ew-span-3,
        .ew-span-4,
        .ew-span-5,
        .ew-span-6 {
            grid-column: span 6 / span 6;
        }

        .ew-span-8 {
            grid-column: span 12 / span 12;
        }
    }

    @media (max-width: 900px) {
        .ew-shell {
            margin: -.75rem;
            padding: 20px;
            border-radius: 22px;
        }

        .ew-header,
        .ew-day-layout,
        .ew-kanban {
            grid-template-columns: 1fr;
        }

        .ew-tools {
            width: 100%;
        }

        .ew-search {
            width: 100%;
            flex: 1;
        }

        .ew-span-3,
        .ew-span-4,
        .ew-span-5,
        .ew-span-6,
        .ew-span-8 {
            grid-column: span 12 / span 12;
        }
    }
</style>

<div class="ew-shell">
    <header class="ew-header">
        <div>
            <h1 class="ew-title">Добрый день, {{ $employee->name }}! 👋</h1>
            <div class="ew-date">{{ $workspace['dateLabel'] }}</div>
        </div>

        <div class="ew-tools">
            <div class="ew-search">
                <span aria-hidden="true">⌕</span>
                <span>Поиск...</span>
            </div>
            <button type="button" class="ew-icon-button" aria-label="Уведомления">!</button>
            <div class="ew-avatar" aria-label="Профиль">{{ $initials ?: 'N' }}</div>
        </div>
    </header>

    <main class="ew-grid">
        <section class="ew-card ew-span-5">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">◷</span>
                    <span>Мой день</span>
                </div>
                <span class="ew-pill">Сегодня</span>
            </div>

            <div class="ew-day-layout">
                <div class="ew-shift-card">
                    <div class="ew-shift-label">Ваша смена</div>
                    <div class="ew-shift-time">{{ $workspace['shiftLabel'] ?? 'Не назначена' }}</div>
                    <div class="ew-status">{{ $workspace['todayStatus'] }}</div>

                    <div class="ew-progress-wrap">
                        <div class="ew-shift-label">Рабочее время сегодня</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 900;">
                            {{ number_format($workspace['hoursToday'], 1, ',', ' ') }} ч
                        </div>
                        <div style="margin-top: 4px; color: #64748b; font-size: 13px; font-weight: 650;">
                            из 8 ч
                        </div>
                        <div class="ew-progress" style="margin-top: 12px;">
                            <div class="ew-progress-bar" style="width: {{ min(100, ($workspace['hoursToday'] / 8) * 100) }}%;"></div>
                        </div>
                    </div>
                </div>

                <div class="ew-timeline">
                    @forelse ($workspace['timeline'] as $item)
                        <div class="ew-timeline-row">
                            <div class="ew-timeline-time">{{ $item['time'] }}</div>
                            <div>
                                <span class="ew-timeline-dot" style="background: {{ $item['color'] }}"></span>
                            </div>
                            <div>
                                <div class="ew-timeline-title">{{ $item['title'] }}</div>
                                <div class="ew-timeline-meta">{{ $item['meta'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="ew-designed-empty">Сегодня смен и событий не назначено.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="ew-card ew-span-4">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">▣</span>
                    <span>Календарь</span>
                </div>
                <a href="{{ $workspace['urls']['calendar'] }}" class="ew-pill">Открыть</a>
            </div>

            <div style="text-align: center; margin-bottom: 18px; font-size: 16px; font-weight: 900;">{{ $workspace['monthLabel'] }}</div>
            <div class="ew-calendar-head">
                @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday)
                    <div class="ew-calendar-weekday">{{ $weekday }}</div>
                @endforeach
            </div>

            <div class="ew-calendar-grid">
                @foreach ($workspace['calendarDays'] as $day)
                    <div class="ew-day-cell {{ $day['isCurrentMonth'] ? '' : 'is-muted' }} {{ $day['isToday'] ? 'is-today' : '' }}">
                        <span>{{ $day['date']->format('j') }}</span>
                        @if (count($day['events']) > 0)
                            <span class="ew-event-dots">
                                @foreach ($day['events'] as $event)
                                    <span class="ew-event-dot" style="background: {{ $event->getCalendarColor() }}"></span>
                                @endforeach
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            <a href="{{ $workspace['urls']['calendar'] }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
                Открыть полный календарь
            </a>
        </section>

        <aside class="ew-span-3 ew-stack">
            <section class="ew-card">
                <div class="ew-card-header" style="margin-bottom: 14px;">
                    <div class="ew-card-title">
                        <span class="ew-symbol" style="background: #fff7ed; color: #f97316;">!</span>
                        <span>Требует внимания</span>
                    </div>
                    @if (count($attentionItems) > 0)
                        <span class="ew-pill" style="background: #fee2e2; color: #dc2626;">{{ count($attentionItems) }}</span>
                    @endif
                </div>

                <div class="ew-alert-list">
                    @forelse ($attentionItems as $item)
                        <div class="ew-alert is-{{ $item['tone'] }}">
                            <div class="ew-alert-icon">!</div>
                            <div>
                                <div class="ew-row-title">{{ $item['title'] }}</div>
                                <div class="ew-row-meta">{{ $item['text'] }}</div>
                            </div>
                            <span aria-hidden="true">›</span>
                        </div>
                    @empty
                        <div class="ew-designed-empty">На сегодня нет важных уведомлений.</div>
                    @endforelse
                </div>
            </section>

            <section class="ew-card">
                <div class="ew-card-header" style="margin-bottom: 14px;">
                    <div class="ew-card-title">
                        <span class="ew-symbol">+</span>
                        <span>Быстрые действия</span>
                    </div>
                </div>

                <div class="ew-action-list">
                    <a href="{{ $workspace['urls']['createRequest'] }}" class="ew-action is-primary">
                        <span>+</span>
                        <span>Создать заявку</span>
                        <span>→</span>
                    </a>
                    <a href="{{ $workspace['urls']['calendar'] }}" class="ew-action">
                        <span>▣</span>
                        <span>Открыть календарь</span>
                        <span>→</span>
                    </a>
                    <a href="{{ $workspace['urls']['documents'] }}" class="ew-action">
                        <span>▤</span>
                        <span>Открыть документы</span>
                        <span>→</span>
                    </a>
                    <span class="ew-action is-muted" title="Будет доступно позже">
                        <span>○</span>
                        <span>Написать сообщение</span>
                        <span>Скоро</span>
                    </span>
                </div>
            </section>
        </aside>

        <section class="ew-card ew-span-8">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">☑</span>
                    <span>Мои задачи</span>
                </div>
                <span class="ew-pill">Скоро</span>
            </div>

            <div class="ew-kanban">
                @foreach (['Новые', 'В работе', 'На проверке', 'Готово'] as $column)
                    <div class="ew-kanban-column">
                        <div class="ew-kanban-title">
                            <span>{{ $column }}</span>
                            <span class="ew-pill" style="padding: 4px 8px;">0</span>
                        </div>
                        <div class="ew-task-placeholder">
                            Модуль задач будет подключён позже.
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="ew-card ew-span-4">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">○</span>
                    <span>Сообщения</span>
                </div>
                <span class="ew-pill">Скоро</span>
            </div>

            <div class="ew-message-list">
                <div class="ew-message-row" style="background: #f8fafc;">
                    <div>
                        <div class="ew-row-title">Корпоративный мессенджер</div>
                        <div class="ew-row-meta">Будет подключён позже.</div>
                    </div>
                    <span class="ew-status-chip">Скоро</span>
                </div>
            </div>
        </section>

        <section class="ew-card ew-span-6">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">□</span>
                    <span>Мои заявки</span>
                </div>
                <span class="ew-pill">{{ $requestCounts['pending'] }}</span>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                <span class="ew-pill">Ожидают {{ $requestCounts['pending'] }}</span>
                <span class="ew-pill" style="background: #dcfce7; color: #15803d;">Одобрены {{ $requestCounts['approved'] }}</span>
                <span class="ew-pill" style="background: #fee2e2; color: #dc2626;">Отклонены {{ $requestCounts['rejected'] }}</span>
                <span class="ew-pill" style="background: #fef3c7; color: #b45309;">Возвращены {{ $requestCounts['returned'] }}</span>
            </div>

            <div class="ew-request-list">
                @forelse ($workspace['recentRequests'] as $request)
                    <div class="ew-request-row">
                        <div>
                            <div class="ew-row-title">{{ $request->getTypeLabel() }}</div>
                            <div class="ew-row-meta">{{ $request->getDateRangeLabel() }}</div>
                        </div>
                        <span class="ew-status-chip {{ $request->status === \App\Models\EmployeeScheduleRequest::STATUS_APPROVED ? 'is-good' : ($request->status === \App\Models\EmployeeScheduleRequest::STATUS_REJECTED ? 'is-bad' : '') }}">
                            {{ $request->getStatusLabel() }}
                        </span>
                    </div>
                @empty
                    <div class="ew-designed-empty">У вас пока нет заявок.</div>
                @endforelse
            </div>

            <a href="{{ $workspace['urls']['requests'] }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
                Открыть все заявки
            </a>
        </section>

        <section id="employee-documents" class="ew-card ew-span-6">
            <div class="ew-card-header">
                <div class="ew-card-title">
                    <span class="ew-symbol">▤</span>
                    <span>Мои документы</span>
                </div>
                <span class="ew-pill" style="{{ $documents['expiredCount'] > 0 ? 'background: #fee2e2; color: #dc2626;' : '' }}">
                    {{ $documents['completeness'] }}%
                </span>
            </div>

            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; gap: 12px; color: #64748b; font-size: 13px; font-weight: 800;">
                    <span>Комплектность</span>
                    <span>{{ $documents['completeness'] }}%</span>
                </div>
                <div class="ew-progress" style="margin-top: 10px;">
                    <div class="ew-progress-bar" style="width: {{ $documents['completeness'] }}%;"></div>
                </div>
            </div>

            <div class="ew-doc-list">
                @forelse ($documents['items'] as $document)
                    <div class="ew-doc-row">
                        <div>
                            <div class="ew-row-title">{{ $document->getCategoryLabel() }}</div>
                            <div class="ew-row-meta">
                                {{ $document->expires_at ? 'до '.$document->expires_at->format('d.m.Y') : 'Без срока действия' }}
                            </div>
                        </div>
                        <span class="ew-status-chip {{ $document->isExpired() ? 'is-bad' : ($document->expiresSoon() ? '' : 'is-good') }}">
                            {{ $document->getExpirationLabel() }}
                        </span>
                    </div>
                @empty
                    <div class="ew-designed-empty">
                        Документы пока не загружены. Не хватает: {{ $documents['missingCount'] }}.
                    </div>
                @endforelse
            </div>

            <a href="{{ $workspace['urls']['documents'] }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
                Открыть все документы
            </a>
        </section>
    </main>
</div>
