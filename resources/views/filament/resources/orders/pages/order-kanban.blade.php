@php
    use App\Models\Order;

    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->join('')
        : 'N';

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => \App\Filament\Pages\Workplace::getUrl()],
            ['label' => 'Заказы', 'icon' => 'package', 'url' => \App\Filament\Resources\Orders\OrderResource::getUrl('index'), 'active' => true],
            ['label' => 'Мой календарь', 'icon' => 'calendar', 'url' => \App\Filament\Pages\MyCalendar::getUrl()],
        ],
        'Рабочие инструменты' => array_values(array_filter([
            ['label' => 'Задачи', 'icon' => 'check-square', 'url' => \App\Filament\Pages\Tasks::getUrl()],
            ['label' => 'Коммуникации', 'icon' => 'message', 'url' => '#', 'badge' => '2', 'sheet' => 'messages'],
            \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::canAccess()
                ? ['label' => 'Заявки', 'icon' => 'link', 'url' => \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index')]
                : null,
            ['label' => 'Справочники', 'icon' => 'book', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
        ])),
        'Продажи' => array_values(array_filter([
            ['label' => 'Заказы', 'icon' => 'package', 'url' => \App\Filament\Resources\Orders\OrderResource::getUrl('index'), 'active' => true],
            \App\Filament\Resources\Customers\CustomerResource::canAccess()
                ? ['label' => 'Покупатели', 'icon' => 'users', 'url' => \App\Filament\Resources\Customers\CustomerResource::getUrl('index')]
                : null,
        ])),
    ];
@endphp

<x-filament-panels::page>
    @component('layouts.work', [
        'title' => 'Заказы',
        'subtitle' => 'Операционный центр обработки заказов',
        'user' => $user,
        'active' => 'orders',
        'showSidebar' => true,
        'appClass' => 'nik-work-app--orders',
    ])
        <div
            class="nik-orders-workspace"
            wire:key="orders-workspace-{{ $showArchive ? 'archive' : 'active' }}"
            x-data="{
                mobileFilters: false,
                activeStatus: '{{ $showArchive ? Order::STATUS_COMPLETED : Order::STATUS_NEW }}',
                statusOrder: @js(array_keys($this->columns)),
                activeSheet: null,
                setActiveStatus(status) { this.activeStatus = status; },
                shiftActiveStatus(direction) {
                    const index = this.statusOrder.indexOf(this.activeStatus);
                    const nextIndex = Math.min(Math.max(index + direction, 0), this.statusOrder.length - 1);
                    this.activeStatus = this.statusOrder[nextIndex] ?? this.activeStatus;
                },
                activeStatusIndex() {
                    return Math.max(0, this.statusOrder.indexOf(this.activeStatus));
                },
                openSheet(sheet) { this.mobileFilters = false; this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); },
                closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); },
            }"
            x-on:keydown.escape.window="mobileFilters = false; closeSheet()"
        >
            <header class="orders-work-mobile-head">
                <div>
                    <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
                    <h1>Заказы</h1>
                    <p>Операционный центр обработки заказов</p>
                </div>
                <div>
                    <button type="button" aria-label="Поиск"><x-work.icon name="search" /></button>
                    <button type="button" aria-label="Уведомления" class="has-badge"><x-work.icon name="bell" /><span>3</span></button>
                    <x-work.user-menu class="orders-work-mobile-user" :user="$user" :initials="$initials" button-class="orders-work-mobile-avatar" :show-chevron="false" />
                </div>
            </header>

            <section class="orders-work-toolbar">
                <label class="orders-work-search">
                    <x-work.icon name="search" />
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Поиск по заказам..." />
                </label>

                <button type="button" class="orders-work-tool-button orders-work-mobile-filter-button" x-on:click="mobileFilters = true">
                    <x-work.icon name="filter" />
                    <span>Фильтры</span>
                </button>

                <label class="orders-work-tool-button orders-work-archive-toggle">
                    <x-work.icon name="archive" />
                    <input type="checkbox" wire:model.live="showArchive" />
                    <span>Архив</span>
                </label>

                <div class="orders-work-view-switch">
                    <button type="button" wire:click="showBoard" class="{{ $viewMode === 'board' ? 'is-active' : '' }}">
                        <x-work.icon name="grid" />
                        <span>Доска</span>
                    </button>
                    <button type="button" wire:click="showList" class="{{ $viewMode === 'list' ? 'is-active' : '' }}">
                        <x-work.icon name="list" />
                        <span>Список</span>
                    </button>
                </div>
            </section>

            <section class="orders-work-kpis" aria-label="Показатели заказов">
                @foreach ($this->kpis as $kpi)
                    <article class="orders-work-kpi is-{{ $kpi['tone'] }}">
                        <span><x-work.icon :name="$kpi['icon']" /></span>
                        <div>
                            <small>{{ $kpi['label'] }}</small>
                            <strong>{{ $kpi['value'] }}</strong>
                            <em>{{ $kpi['delta'] }}</em>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="orders-work-filters" aria-label="Фильтры заказов">
                <label>
                    <span>Тип получения</span>
                    <select wire:model.live="fulfillmentMethod">
                        @foreach ($this->fulfillmentMethodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Оплата</span>
                    <select wire:model.live="paymentStatus">
                        @foreach ($this->paymentStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>SLA</span>
                    <select wire:model.live="sla">
                        @foreach ($this->slaOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($showArchive)
                    <div class="orders-work-period">
                        <label>
                            <span>Архив с</span>
                            <input type="date" wire:model.live="archivedFrom" />
                        </label>
                        <label>
                            <span>по</span>
                            <input type="date" wire:model.live="archivedTo" />
                        </label>
                    </div>
                @endif

                <button type="button" wire:click="resetFilters">Сбросить</button>
            </section>

            <section class="orders-work-mobile-statuses" aria-label="Статусы заказов">
                @foreach ($this->columns as $status => $column)
                    <button
                        type="button"
                        data-status="{{ $status }}"
                        x-on:click="setActiveStatus('{{ $status }}')"
                        x-bind:class="{ 'is-active': activeStatus === '{{ $status }}' }"
                    >
                        <span>{{ match ($status) {
                            Order::STATUS_NEW => 'Новый',
                            Order::STATUS_ASSEMBLING => 'Сборка',
                            Order::STATUS_READY_FOR_DISPATCH => 'Отгрузка',
                            Order::STATUS_COMPLETED => 'Завершён',
                            default => 'Отменён',
                        } }}</span>
                        <em>{{ $column['orders']->count() }}</em>
                    </button>
                @endforeach
            </section>

            @if ($viewMode === 'board')
                <section
                    class="orders-work-board {{ $showArchive ? 'is-archive' : '' }}"
                    x-data="{
                        draggedOrderId: null,
                        draggedStatus: null,
                        overStatus: null,
                        mobileDragActive: false,
                        mobileDragElement: null,
                        swipeStartX: 0,
                        swipeStartY: 0,
                        swipeDeltaX: 0,
                        swipePointerId: null,
                        swipeActive: false,
                        swipeTracking: false,
                        dropOrder(targetStatus) {
                            if (! this.draggedOrderId || ! targetStatus || this.draggedStatus === targetStatus) {
                                this.overStatus = null;
                                this.mobileDragActive = false;
                                this.mobileDragElement = null;
                                return;
                            }
                            this.$wire.moveOrder(Number(this.draggedOrderId), targetStatus);
                            this.draggedOrderId = null;
                            this.draggedStatus = null;
                            this.overStatus = null;
                            this.mobileDragActive = false;
                            this.mobileDragElement = null;
                        },
                        mobileStatusFromPoint(x, y) {
                            const element = document.elementFromPoint(x, y);
                            const target = element?.closest('[data-status]');
                            return target?.dataset.status ?? null;
                        },
                        setMobileOverStatus(x, y) {
                            this.overStatus = this.mobileStatusFromPoint(x, y);
                        },
                        isSwipeIgnoredTarget(target) {
                            return Boolean(target.closest('button, input, select, textarea, [role=button], .orders-work-order-actions'));
                        },
                        startBoardSwipe(event) {
                            if (event.pointerType !== 'touch' || this.mobileDragActive || this.isSwipeIgnoredTarget(event.target)) {
                                return;
                            }

                            this.swipeStartX = event.clientX;
                            this.swipeStartY = event.clientY;
                            this.swipeDeltaX = 0;
                            this.swipePointerId = event.pointerId;
                            this.swipeTracking = true;
                            this.swipeActive = false;
                        },
                        moveBoardSwipe(event) {
                            if (! this.swipeTracking || event.pointerId !== this.swipePointerId || this.mobileDragActive) {
                                return;
                            }

                            const deltaX = event.clientX - this.swipeStartX;
                            const deltaY = event.clientY - this.swipeStartY;

                            if (! this.swipeActive && Math.abs(deltaX) > 12 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25) {
                                this.swipeActive = true;
                                event.currentTarget.setPointerCapture?.(event.pointerId);
                            }

                            if (this.swipeActive) {
                                event.preventDefault();
                                const atFirst = activeStatusIndex() === 0 && deltaX > 0;
                                const atLast = activeStatusIndex() === statusOrder.length - 1 && deltaX < 0;
                                this.swipeDeltaX = atFirst || atLast ? deltaX * .32 : deltaX;
                            }
                        },
                        finishBoardSwipe(event) {
                            if (! this.swipeTracking || event.pointerId !== this.swipePointerId) {
                                return;
                            }

                            if (this.swipeActive) {
                                event.preventDefault();

                                const threshold = Math.min(92, Math.max(54, event.currentTarget.clientWidth * .18));

                                if (Math.abs(this.swipeDeltaX) > threshold) {
                                    shiftActiveStatus(this.swipeDeltaX < 0 ? 1 : -1);
                                }
                            }

                            this.swipeDeltaX = 0;
                            this.swipePointerId = null;
                            this.swipeTracking = false;
                            this.swipeActive = false;
                        },
                        cancelBoardSwipe() {
                            this.swipeDeltaX = 0;
                            this.swipePointerId = null;
                            this.swipeTracking = false;
                            this.swipeActive = false;
                        },
                    }"
                    x-bind:class="{ 'is-mobile-dragging': mobileDragActive, 'is-mobile-swiping': swipeActive }"
                    x-on:pointerdown="startBoardSwipe($event)"
                    x-on:pointermove="moveBoardSwipe($event)"
                    x-on:pointerup="finishBoardSwipe($event)"
                    x-on:pointercancel="cancelBoardSwipe()"
                >
                    <div
                        class="orders-work-board-track"
                        x-bind:style="`--orders-work-active-index: ${activeStatusIndex()}; --orders-work-swipe-x: ${swipeDeltaX}px;`"
                    >
                        @foreach ($this->columns as $status => $column)
                            <section
                                class="orders-work-column"
                                data-status="{{ $status }}"
                                x-bind:class="{ 'is-over': overStatus === '{{ $status }}', 'is-mobile-active': activeStatus === '{{ $status }}' }"
                                x-on:dragover.prevent="if (draggedOrderId) overStatus = '{{ $status }}'"
                                x-on:dragleave="if ($event.currentTarget === $event.target) overStatus = null"
                                x-on:drop.prevent="dropOrder('{{ $status }}')"
                            >
                                <header>
                                    <div>
                                        <h2>{{ $column['label'] }}</h2>
                                        <p>{{ $column['orders']->count() }} заказов</p>
                                    </div>
                                    <span>{{ $column['orders']->count() }}</span>
                                </header>

                                <div class="orders-work-column-list">
                                    @forelse ($column['orders'] as $order)
                                        @include('filament.resources.orders.pages.partials.order-card', ['order' => $order, 'archiveMode' => $showArchive])
                                    @empty
                                        <div class="orders-work-empty">
                                            <strong>Нет заказов в этом статусе</strong>
                                            <span>Заказы появятся здесь после оформления.</span>
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="orders-work-list">
                    <header>
                        <span>Заказ</span>
                        <span>Покупатель</span>
                        <span>Получение</span>
                        <span>Статус</span>
                        <span>Оплата</span>
                        <span>SLA</span>
                        <span>Сумма</span>
                        <span></span>
                    </header>

                    @forelse ($this->listOrders as $order)
                        <article>
                            <strong>{{ $order->order_number }}</strong>
                            <span>{{ $this->customerName($order) }}</span>
                            <span>{{ $order->getFulfillmentMethodLabel() }}</span>
                            <span>{{ $order->getStatusLabel() }}</span>
                            <span>{{ $this->paymentStatusLabel($order) }}</span>
                            <span class="orders-work-sla is-{{ $order->getSlaState() }}">{{ $order->getSlaLabel() }}</span>
                            <strong>{{ $this->money($order->total) }}</strong>
                            <a href="{{ $this->viewOrderUrl($order) }}">Открыть</a>
                        </article>
                    @empty
                        <div class="orders-work-empty">
                            <strong>По выбранным фильтрам ничего не найдено</strong>
                            <span>Измените фильтры или сбросьте поиск.</span>
                        </div>
                    @endforelse
                </section>
            @endif

            <section class="orders-work-analytics">
                <article>
                    <h3>SLA контроль</h3>
                    <strong>{{ $this->analytics['slaPercent'] }}%</strong>
                    <span>заказов в срок</span>
                    <em>{{ $this->analytics['overdue'] }} просрочены</em>
                </article>
                <article>
                    <h3>Тип получения</h3>
                    <strong>{{ $this->analytics['deliveryPercent'] }}%</strong>
                    <span>доставка</span>
                    <em>{{ $this->analytics['pickupPercent'] }}% самовывоз</em>
                </article>
                <article>
                    <h3>Оплата</h3>
                    <strong>{{ $this->analytics['paidPercent'] }}%</strong>
                    <span>оплачено</span>
                    <em>всего {{ $this->analytics['total'] }} заказов</em>
                </article>
            </section>

            <div class="orders-work-sheet-backdrop" x-cloak x-show="mobileFilters" x-transition.opacity x-on:click="mobileFilters = false"></div>
            <section class="orders-work-filter-sheet" x-cloak x-show="mobileFilters" x-transition role="dialog" aria-modal="true" aria-label="Фильтры заказов">
                <div class="orders-work-sheet-handle"></div>
                <header>
                    <div>
                        <span>Заказы</span>
                        <h2>Фильтры</h2>
                    </div>
                    <button type="button" x-on:click="mobileFilters = false" aria-label="Закрыть">×</button>
                </header>

                <div class="orders-work-sheet-fields">
                    <label>
                        <span>Тип получения</span>
                        <select wire:model.live="fulfillmentMethod">
                            @foreach ($this->fulfillmentMethodOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Оплата</span>
                        <select wire:model.live="paymentStatus">
                            @foreach ($this->paymentStatusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>SLA</span>
                        <select wire:model.live="sla">
                            @foreach ($this->slaOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="orders-work-sheet-check">
                        <input type="checkbox" wire:model.live="showArchive" />
                        <span>Показывать архив</span>
                    </label>
                    @if ($showArchive)
                        <div class="orders-work-period">
                            <label>
                                <span>Архив с</span>
                                <input type="date" wire:model.live="archivedFrom" />
                            </label>
                            <label>
                                <span>по</span>
                                <input type="date" wire:model.live="archivedTo" />
                            </label>
                        </div>
                    @endif
                    <button type="button" wire:click="resetFilters" x-on:click="mobileFilters = false">Сбросить</button>
                </div>
            </section>

            <x-work.mobile-bottom-sheets :menu-groups="$mobileMenuGroups" />
            <x-work.mobile-bottom-nav />
        </div>

        @if ($archiveOrderId !== null)
            <div class="orders-work-modal-backdrop">
                <section class="orders-work-modal">
                    <h2>Архивировать заказ?</h2>
                    <p>После архивирования заказ исчезнет с доски, но останется доступным через архив.</p>
                    <div>
                        <button type="button" wire:click="archiveConfirmed">Архивировать</button>
                        <button type="button" wire:click="cancelArchive">Отмена</button>
                    </div>
                </section>
            </div>
        @endif
    @endcomponent
</x-filament-panels::page>
