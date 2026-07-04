@php
    $employee = $workspace['employee'];
    $documents = $workspace['documents'];
    $requestCounts = $workspace['requestCounts'];
    $attentionItems = $workspace['attentionItems'];
@endphp

@component('layouts.work', [
    'title' => 'Добрый день, '.$employee->name.'! 👋',
    'subtitle' => $workspace['dateLabel'],
    'user' => $employee,
    'active' => 'workplace',
    'showSidebar' => false,
])
    <main class="nik-work-grid">
        <x-work.card class="nik-work-span-5" title="Мой день" icon="◷">
            <x-slot:actions>
                <x-work.badge>Сегодня</x-work.badge>
            </x-slot:actions>

            <div class="nik-work-day-layout">
                <div class="nik-work-shift-card">
                    <div class="nik-work-label">Ваша смена</div>
                    <div class="nik-work-shift-time">{{ $workspace['shiftLabel'] ?? 'Не назначена' }}</div>
                    <div class="nik-work-status">{{ $workspace['todayStatus'] }}</div>

                    <div class="nik-work-progress-wrap">
                        <div class="nik-work-label">Рабочее время сегодня</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 900;">
                            {{ number_format($workspace['hoursToday'], 1, ',', ' ') }} ч
                        </div>
                        <div style="margin-top: 4px; color: #64748b; font-size: 13px; font-weight: 650;">
                            из 8 ч
                        </div>
                        <div class="nik-work-progress" style="margin-top: 12px;">
                            <div class="nik-work-progress-bar" style="width: {{ min(100, ($workspace['hoursToday'] / 8) * 100) }}%;"></div>
                        </div>
                    </div>
                </div>

                <x-work.timeline :items="$workspace['timeline']" />
            </div>
        </x-work.card>

        <x-work.card class="nik-work-span-4" title="Календарь" icon="▣">
            <x-slot:actions>
                <a href="{{ $workspace['urls']['calendar'] }}" class="nik-work-badge">Открыть</a>
            </x-slot:actions>

            <x-work.calendar-mini
                :month-label="$workspace['monthLabel']"
                :days="$workspace['calendarDays']"
                :url="$workspace['urls']['calendar']"
            />
        </x-work.card>

        <aside class="nik-work-span-3 nik-work-stack">
            <x-work.card title="Требует внимания" icon="!">
                <x-slot:actions>
                    @if (count($attentionItems) > 0)
                        <x-work.badge tone="red">{{ count($attentionItems) }}</x-work.badge>
                    @endif
                </x-slot:actions>

                <div class="nik-work-list">
                    @forelse ($attentionItems as $item)
                        <div class="nik-work-alert is-{{ $item['tone'] }}">
                            <div class="nik-work-alert-icon">!</div>
                            <div>
                                <div class="nik-work-row-title">{{ $item['title'] }}</div>
                                <div class="nik-work-row-meta">{{ $item['text'] }}</div>
                            </div>
                            <span aria-hidden="true">›</span>
                        </div>
                    @empty
                        <x-work.placeholder badge="">На сегодня нет важных уведомлений.</x-work.placeholder>
                    @endforelse
                </div>
            </x-work.card>

            <x-work.card title="Быстрые действия" icon="+">
                <div class="nik-work-list">
                    <x-work.action-row
                        :href="$workspace['urls']['createRequest']"
                        icon="+"
                        label="Создать заявку"
                        primary
                    />
                    <x-work.action-row
                        :href="$workspace['urls']['calendar']"
                        icon="▣"
                        label="Открыть календарь"
                    />
                    <x-work.action-row
                        :href="$workspace['urls']['documents']"
                        icon="▤"
                        label="Открыть документы"
                    />
                    <x-work.action-row
                        icon="○"
                        label="Написать сообщение"
                        suffix="Скоро"
                        disabled
                    />
                </div>
            </x-work.card>
        </aside>

        <x-work.card class="nik-work-span-8" title="Мои задачи" icon="☑">
            <x-slot:actions>
                <x-work.badge tone="gray">Скоро</x-work.badge>
            </x-slot:actions>

            <x-work.kanban-preview />
        </x-work.card>

        <x-work.card class="nik-work-span-4" title="Сообщения" icon="○">
            <x-slot:actions>
                <x-work.badge tone="gray">Скоро</x-work.badge>
            </x-slot:actions>

            <div class="nik-work-list">
                <div class="nik-work-row" style="background: #f8fafc;">
                    <div>
                        <div class="nik-work-row-title">Корпоративный мессенджер</div>
                        <div class="nik-work-row-meta">Будет подключён позже.</div>
                    </div>
                    <x-work.badge tone="gray">Скоро</x-work.badge>
                </div>
            </div>
        </x-work.card>

        <x-work.card class="nik-work-span-6" title="Мои заявки" icon="□">
            <x-slot:actions>
                <x-work.badge>{{ $requestCounts['pending'] }}</x-work.badge>
            </x-slot:actions>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                <x-work.badge>Ожидают {{ $requestCounts['pending'] }}</x-work.badge>
                <x-work.badge tone="green">Одобрены {{ $requestCounts['approved'] }}</x-work.badge>
                <x-work.badge tone="red">Отклонены {{ $requestCounts['rejected'] }}</x-work.badge>
                <x-work.badge tone="amber">Возвращены {{ $requestCounts['returned'] }}</x-work.badge>
            </div>

            <div class="nik-work-list">
                @forelse ($workspace['recentRequests'] as $request)
                    <div class="nik-work-row">
                        <div>
                            <div class="nik-work-row-title">{{ $request->getTypeLabel() }}</div>
                            <div class="nik-work-row-meta">{{ $request->getDateRangeLabel() }}</div>
                        </div>
                        <x-work.badge :tone="$request->status === \App\Models\EmployeeScheduleRequest::STATUS_APPROVED ? 'green' : ($request->status === \App\Models\EmployeeScheduleRequest::STATUS_REJECTED ? 'red' : 'amber')">
                            {{ $request->getStatusLabel() }}
                        </x-work.badge>
                    </div>
                @empty
                    <x-work.placeholder badge="">У вас пока нет заявок.</x-work.placeholder>
                @endforelse
            </div>

            <a href="{{ $workspace['urls']['requests'] }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
                Открыть все заявки
            </a>
        </x-work.card>

        <x-work.card id="employee-documents" class="nik-work-span-6" title="Мои документы" icon="▤">
            <x-slot:actions>
                <x-work.badge :tone="$documents['expiredCount'] > 0 ? 'red' : 'blue'">
                    {{ $documents['completeness'] }}%
                </x-work.badge>
            </x-slot:actions>

            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; gap: 12px; color: #64748b; font-size: 13px; font-weight: 800;">
                    <span>Комплектность</span>
                    <span>{{ $documents['completeness'] }}%</span>
                </div>
                <div class="nik-work-progress" style="margin-top: 10px;">
                    <div class="nik-work-progress-bar" style="width: {{ $documents['completeness'] }}%;"></div>
                </div>
            </div>

            <div class="nik-work-list">
                @forelse ($documents['items'] as $document)
                    <div class="nik-work-row">
                        <div>
                            <div class="nik-work-row-title">{{ $document->getCategoryLabel() }}</div>
                            <div class="nik-work-row-meta">
                                {{ $document->expires_at ? 'до '.$document->expires_at->format('d.m.Y') : 'Без срока действия' }}
                            </div>
                        </div>
                        <x-work.badge :tone="$document->isExpired() ? 'red' : ($document->expiresSoon() ? 'amber' : 'green')">
                            {{ $document->getExpirationLabel() }}
                        </x-work.badge>
                    </div>
                @empty
                    <x-work.placeholder badge="">
                        Документы пока не загружены. Не хватает: {{ $documents['missingCount'] }}.
                    </x-work.placeholder>
                @endforelse
            </div>

            <a href="{{ $workspace['urls']['documents'] }}" style="display: block; margin-top: 20px; color: #2563eb; font-size: 14px; font-weight: 850; text-align: center; text-decoration: none;">
                Открыть все документы
            </a>
        </x-work.card>
    </main>
@endcomponent
