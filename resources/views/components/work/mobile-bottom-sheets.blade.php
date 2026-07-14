@props([
    'menuGroups' => [],
    'requestCounts' => [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'returned' => 0,
    ],
    'recentRequests' => [],
    'createRequestUrl' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('create'),
    'requestsUrl' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index'),
])

@php
    $requestTone = fn (?string $status): string => match ($status) {
        \App\Models\EmployeeScheduleRequest::STATUS_APPROVED => 'green',
        \App\Models\EmployeeScheduleRequest::STATUS_REJECTED => 'red',
        \App\Models\EmployeeScheduleRequest::STATUS_RETURNED => 'amber',
        \App\Models\EmployeeScheduleRequest::STATUS_CANCELLED => 'gray',
        \App\Models\EmployeeScheduleRequest::STATUS_IN_REVIEW,
        \App\Models\EmployeeScheduleRequest::STATUS_FORWARDED => 'blue',
        default => 'amber',
    };
    $menuGroups = \App\Support\WorkNavigation::mergeMenuGroups($menuGroups, auth()->user(), $active ?? null);
@endphp

<div
    class="nik-work-mobile-sheet-backdrop"
    x-cloak
    x-show="activeSheet"
    x-transition.opacity.duration.200ms
    x-on:click="closeSheet()"
    aria-hidden="true"
></div>

<section
    class="nik-work-mobile-sheet"
    x-cloak
    x-show="activeSheet === 'messages'"
    x-bind:hidden="activeSheet !== 'messages'"
    x-bind:aria-hidden="activeSheet !== 'messages'"
    x-bind:style="activeSheet === 'messages' ? 'transform: translateY(0); opacity: 1;' : null"
    x-transition:enter="nik-work-sheet-enter"
    x-transition:enter-start="nik-work-sheet-enter-start"
    x-transition:enter-end="nik-work-sheet-enter-end"
    x-transition:leave="nik-work-sheet-leave"
    x-transition:leave-start="nik-work-sheet-leave-start"
    x-transition:leave-end="nik-work-sheet-leave-end"
    role="dialog"
    aria-modal="true"
    aria-label="Коммуникации"
>
    <div class="nik-work-mobile-sheet-handle"></div>
    <header class="nik-work-mobile-sheet-head">
        <div>
            <span>Центр связи</span>
            <h2>Коммуникации</h2>
        </div>
        <button type="button" x-on:click="closeSheet()" aria-label="Закрыть">
            <x-work.icon name="plus" />
        </button>
    </header>
    <div class="nik-work-mobile-sheet-tabs">
        <span class="is-active">Мессенджер</span>
        <span>Почта</span>
    </div>
    <div class="nik-work-mobile-sheet-grid">
        <div class="nik-work-mobile-sheet-placeholder">
            <x-work.icon name="message" />
            <strong>Мессенджер</strong>
            <p>Корпоративный мессенджер будет подключён позже.</p>
        </div>
        <div class="nik-work-mobile-sheet-placeholder">
            <x-work.icon name="file" />
            <strong>Почта</strong>
            <p>Почтовый клиент будет подключён позже.</p>
        </div>
    </div>
</section>

<section
    class="nik-work-mobile-sheet nik-work-mobile-sheet--large"
    x-cloak
    x-show="activeSheet === 'menu'"
    x-bind:hidden="activeSheet !== 'menu'"
    x-bind:aria-hidden="activeSheet !== 'menu'"
    x-bind:style="activeSheet === 'menu' ? 'transform: translateY(0); opacity: 1;' : null"
    x-transition:enter="nik-work-sheet-enter"
    x-transition:enter-start="nik-work-sheet-enter-start"
    x-transition:enter-end="nik-work-sheet-enter-end"
    x-transition:leave="nik-work-sheet-leave"
    x-transition:leave-start="nik-work-sheet-leave-start"
    x-transition:leave-end="nik-work-sheet-leave-end"
    role="dialog"
    aria-modal="true"
    aria-label="Меню"
>
    <div class="nik-work-mobile-sheet-handle"></div>
    <header class="nik-work-mobile-sheet-head">
        <div>
            <span>Навигация</span>
            <h2>Меню</h2>
        </div>
        <button type="button" x-on:click="closeSheet()" aria-label="Закрыть">
            <x-work.icon name="plus" />
        </button>
    </header>
    <div class="nik-work-mobile-menu-groups">
        @foreach ($menuGroups as $group => $items)
            @if (count($items))
                <section>
                    <h3>{{ $group }}</h3>
                    <div class="nik-work-mobile-menu-list">
                        @foreach ($items as $item)
                            @if (($item['sheet'] ?? null) && ! ($item['disabled'] ?? false))
                                <button type="button" x-on:click="openSheet('{{ $item['sheet'] }}')">
                                    <span><x-work.icon :name="$item['icon']" /></span>
                                    <strong>{{ $item['label'] }}</strong>
                                    @isset($item['badge'])
                                        <em>{{ $item['badge'] }}</em>
                                    @endisset
                                </button>
                            @elseif ($item['disabled'] ?? false)
                                <span>
                                    <span><x-work.icon :name="$item['icon']" /></span>
                                    <strong>{{ $item['label'] }}</strong>
                                    @isset($item['badge'])
                                        <em>{{ $item['badge'] }}</em>
                                    @endisset
                                </span>
                            @else
                                <a href="{{ $item['url'] }}" class="{{ ($item['active'] ?? false) ? 'is-active' : '' }}">
                                    <span><x-work.icon :name="$item['icon']" /></span>
                                    <strong>{{ $item['label'] }}</strong>
                                    @isset($item['badge'])
                                        <em>{{ $item['badge'] }}</em>
                                    @endisset
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</section>

<section
    class="nik-work-mobile-sheet"
    x-cloak
    x-show="activeSheet === 'tasks'"
    x-bind:hidden="activeSheet !== 'tasks'"
    x-bind:aria-hidden="activeSheet !== 'tasks'"
    x-bind:style="activeSheet === 'tasks' ? 'transform: translateY(0); opacity: 1;' : null"
    x-transition:enter="nik-work-sheet-enter"
    x-transition:enter-start="nik-work-sheet-enter-start"
    x-transition:enter-end="nik-work-sheet-enter-end"
    x-transition:leave="nik-work-sheet-leave"
    x-transition:leave-start="nik-work-sheet-leave-start"
    x-transition:leave-end="nik-work-sheet-leave-end"
    role="dialog"
    aria-modal="true"
    aria-label="Мои задачи"
>
    <div class="nik-work-mobile-sheet-handle"></div>
    <header class="nik-work-mobile-sheet-head">
        <div>
            <span>Задачи</span>
            <h2>Мои задачи</h2>
        </div>
        <button type="button" x-on:click="closeSheet()" aria-label="Закрыть">
            <x-work.icon name="plus" />
        </button>
    </header>
    <div class="nik-work-mobile-task-sheet">
        @foreach (['Новые', 'В работе', 'На проверке', 'Готово'] as $column)
            <div>
                <strong>{{ $column }}</strong>
                <span>0</span>
                <p>Модуль задач будет подключён позже.</p>
            </div>
        @endforeach
    </div>
</section>

<section
    class="nik-work-mobile-sheet nik-work-mobile-sheet--requests"
    x-cloak
    x-show="activeSheet === 'requests'"
    x-bind:hidden="activeSheet !== 'requests'"
    x-bind:aria-hidden="activeSheet !== 'requests'"
    x-bind:style="activeSheet === 'requests' ? 'transform: translateY(0); opacity: 1;' : null"
    x-transition:enter="nik-work-sheet-enter"
    x-transition:enter-start="nik-work-sheet-enter-start"
    x-transition:enter-end="nik-work-sheet-enter-end"
    x-transition:leave="nik-work-sheet-leave"
    x-transition:leave-start="nik-work-sheet-leave-start"
    x-transition:leave-end="nik-work-sheet-leave-end"
    role="dialog"
    aria-modal="true"
    aria-label="Мои заявки"
>
    <div class="nik-work-mobile-sheet-handle"></div>
    <header class="nik-work-mobile-sheet-head">
        <div>
            <span>Кадровые заявки</span>
            <h2>Заявки</h2>
        </div>
        <button type="button" x-on:click="closeSheet()" aria-label="Закрыть">
            <x-work.icon name="plus" />
        </button>
    </header>

    <div class="nik-work-mobile-request-stats">
        <a href="{{ $requestsUrl }}">
            <span>Ожидают</span>
            <strong>{{ $requestCounts['pending'] ?? 0 }}</strong>
        </a>
        <a href="{{ $requestsUrl }}">
            <span>Одобрены</span>
            <strong>{{ $requestCounts['approved'] ?? 0 }}</strong>
        </a>
        <a href="{{ $requestsUrl }}">
            <span>Отклонены</span>
            <strong>{{ $requestCounts['rejected'] ?? 0 }}</strong>
        </a>
        <a href="{{ $requestsUrl }}">
            <span>Возвращены</span>
            <strong>{{ $requestCounts['returned'] ?? 0 }}</strong>
        </a>
    </div>

    <div class="nik-work-mobile-sheet-actions nik-work-mobile-request-actions">
        <a href="{{ $createRequestUrl }}"><x-work.icon name="plus" /> Создать заявку</a>
        <a href="{{ $requestsUrl }}"><x-work.icon name="list" /> Все заявки</a>
    </div>

    <div class="nik-work-mobile-request-list">
        @forelse ($recentRequests as $request)
            <a
                class="nik-work-mobile-request-row is-{{ $requestTone($request->status ?? null) }}"
                href="{{ \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('view', ['record' => $request]) }}"
            >
                <span class="nik-work-mobile-request-icon"><x-work.icon name="link" /></span>
                <div>
                    <strong>{{ $request->getTypeLabel() }}</strong>
                    <small>{{ $request->getDateRangeLabel() }}</small>
                </div>
                <em>{{ $request->getStatusLabel() }}</em>
                <i><x-work.icon name="chevron-right" /></i>
            </a>
        @empty
            <div class="nik-work-mobile-request-empty">
                <x-work.icon name="file" />
                <strong>Заявок пока нет</strong>
                <span>Создайте заявку на отпуск, отгул, смену или событие календаря.</span>
            </div>
        @endforelse
    </div>
</section>
