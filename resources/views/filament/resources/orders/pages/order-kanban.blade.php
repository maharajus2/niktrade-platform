@php
    use App\Models\Order;

    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->join('')
        : 'N';
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
            x-data="{ mobileFilters: false, activeStatus: '{{ Order::STATUS_NEW }}' }"
            x-on:keydown.escape.window="mobileFilters = false"
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
                    <span>{{ $initials }}</span>
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

                <button type="button" wire:click="resetFilters">Сбросить</button>
            </section>

            <section class="orders-work-mobile-statuses" aria-label="Статусы заказов">
                @foreach ($this->columns as $status => $column)
                    <button type="button" x-on:click="activeStatus = '{{ $status }}'" x-bind:class="{ 'is-active': activeStatus === '{{ $status }}' }">
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
                    class="orders-work-board"
                    x-data="{
                        draggedOrderId: null,
                        draggedStatus: null,
                        overStatus: null,
                        dropOrder(targetStatus) {
                            if (! this.draggedOrderId || ! targetStatus || this.draggedStatus === targetStatus) {
                                this.overStatus = null;
                                return;
                            }
                            this.$wire.moveOrder(Number(this.draggedOrderId), targetStatus);
                            this.draggedOrderId = null;
                            this.draggedStatus = null;
                            this.overStatus = null;
                        },
                    }"
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
                                    @include('filament.resources.orders.pages.partials.order-card', ['order' => $order])
                                @empty
                                    <div class="orders-work-empty">
                                        <strong>Нет заказов в этом статусе</strong>
                                        <span>Заказы появятся здесь после оформления.</span>
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
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
                    <button type="button" wire:click="resetFilters" x-on:click="mobileFilters = false">Сбросить</button>
                </div>
            </section>

            <nav class="orders-work-mobile-bottom" aria-label="Быстрая навигация">
                <a class="is-active" href="{{ \App\Filament\Pages\Workplace::getUrl() }}"><x-work.icon name="home" /><span>Главная</span></a>
                <button type="button"><x-work.icon name="message" /><span>Мессенджер</span></button>
                <button type="button" class="orders-work-mobile-menu"><strong><x-work.icon name="grid" /></strong><span>Меню</span></button>
                <button type="button"><x-work.icon name="check-square" /><span>Задачи</span></button>
                <a href="{{ \App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource::getUrl('index') }}"><x-work.icon name="file" /><span>Заявки</span></a>
            </nav>
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
