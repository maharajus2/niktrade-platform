<x-filament-panels::page>
    <div style="display: grid; gap: 1rem;">
        <x-filament::section>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; align-items: end;">
                <label style="display: grid; gap: 0.375rem; font-size: 0.875rem;">
                    <span style="font-weight: 600; color: rgb(55, 65, 81);">Тип получения</span>
                    <select
                        wire:model.live="fulfillmentMethod"
                        style="width: 100%; min-height: 2.5rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.5rem 0.75rem; color: rgb(17, 24, 39); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);"
                    >
                        @foreach ($this->fulfillmentMethodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label style="display: grid; gap: 0.375rem; font-size: 0.875rem;">
                    <span style="font-weight: 600; color: rgb(55, 65, 81);">Оплата</span>
                    <select
                        wire:model.live="paymentStatus"
                        style="width: 100%; min-height: 2.5rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.5rem 0.75rem; color: rgb(17, 24, 39); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);"
                    >
                        @foreach ($this->paymentStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label style="display: grid; gap: 0.375rem; font-size: 0.875rem;">
                    <span style="font-weight: 600; color: rgb(55, 65, 81);">SLA</span>
                    <select
                        wire:model.live="sla"
                        style="width: 100%; min-height: 2.5rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.5rem 0.75rem; color: rgb(17, 24, 39); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);"
                    >
                        @foreach ($this->slaOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <label style="display: inline-flex; min-height: 2.5rem; align-items: center; gap: 0.5rem; border: 1px solid rgb(229, 231, 235); border-radius: 0.5rem; background: #fff; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 600; color: rgb(55, 65, 81); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);">
                        <input
                            type="checkbox"
                            wire:model.live="showArchive"
                            style="width: 1rem; height: 1rem;"
                        >
                        <span>Показывать архив</span>
                    </label>

                    <button
                        type="button"
                        wire:click="resetFilters"
                        style="min-height: 2.5rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.5rem 0.875rem; color: rgb(55, 65, 81); font-size: 0.875rem; font-weight: 700; cursor: pointer;"
                    >
                        Сбросить
                    </button>
                </div>
            </div>
        </x-filament::section>

        <div
            x-data="{
                draggedOrderId: null,
                draggedStatus: null,
                overStatus: null,
                terminalMessages: {
                    '{{ \App\Models\Order::STATUS_COMPLETED }}': {
                        title: 'Завершить заказ?',
                        text: 'Заказ будет переведён в статус «Завершён».',
                    },
                    '{{ \App\Models\Order::STATUS_CANCELLED }}': {
                        title: 'Отменить заказ?',
                        text: 'Заказ будет переведён в статус «Отменён». Покупатель получит уведомление, если Telegram подключён.',
                    },
                },
                dropOrder(targetStatus) {
                    if (! this.draggedOrderId || ! targetStatus || this.draggedStatus === targetStatus) {
                        this.overStatus = null;
                        return;
                    }

                    const message = this.terminalMessages[targetStatus];

                    if (message && ! window.confirm(message.title + '\n\n' + message.text)) {
                        this.overStatus = null;
                        return;
                    }

                    this.$wire.moveOrder(Number(this.draggedOrderId), targetStatus);
                    this.draggedOrderId = null;
                    this.draggedStatus = null;
                    this.overStatus = null;
                },
            }"
            style="height: calc(100vh - 18rem); min-height: 420px; max-height: 780px; overflow-x: auto; overflow-y: hidden; padding-bottom: 0.75rem;"
        >
            <div style="display: flex; gap: 1rem; min-width: max-content; height: 100%; align-items: stretch;">
                @foreach ($this->columns as $status => $column)
                    <section
                        data-status="{{ $status }}"
                        x-on:dragover.prevent="if (draggedOrderId) overStatus = '{{ $status }}'"
                        x-on:dragleave="if ($event.currentTarget === $event.target) overStatus = null"
                        x-on:drop.prevent="dropOrder('{{ $status }}')"
                        x-bind:style="`width: 320px; min-width: 320px; height: 100%; display: flex; flex-direction: column; border: 1px solid ${overStatus === '{{ $status }}' ? 'rgb(22, 101, 52)' : 'rgb(229, 231, 235)'}; border-radius: 0.875rem; background: ${overStatus === '{{ $status }}' ? 'rgb(240, 253, 244)' : 'rgb(249, 250, 251)'}; padding: 0.75rem; box-shadow: ${overStatus === '{{ $status }}' ? 'inset 0 0 0 2px rgba(22, 101, 52, 0.16)' : 'none'};`"
                    >
                        <header style="position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin: -0.75rem -0.75rem 0.75rem; padding: 0.75rem; border-bottom: 1px solid rgb(229, 231, 235); border-radius: 0.875rem 0.875rem 0 0; background: rgb(249, 250, 251);">
                            <div>
                                <h2 style="margin: 0; color: rgb(17, 24, 39); font-size: 0.95rem; font-weight: 800;">{{ $column['label'] }}</h2>
                                <p style="margin: 0.15rem 0 0; color: rgb(107, 114, 128); font-size: 0.75rem;">{{ $column['orders']->count() }} заказов</p>
                            </div>
                            <span style="display: inline-flex; min-width: 1.75rem; justify-content: center; border-radius: 999px; background: #fff; padding: 0.25rem 0.55rem; color: rgb(75, 85, 99); font-size: 0.75rem; font-weight: 800; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);">
                                {{ $column['orders']->count() }}
                            </span>
                        </header>

                        <div style="display: grid; gap: 0.75rem; flex: 1; min-height: 0; overflow-y: auto; padding-right: 0.25rem; align-content: start;">
                            @forelse ($column['orders'] as $order)
                                @php
                                    $slaState = $order->getSlaState();
                                    $accentBorder = match ($slaState) {
                                        \App\Models\Order::SLA_STATE_OVERDUE => 'rgb(239, 68, 68)',
                                        \App\Models\Order::SLA_STATE_WARNING => 'rgb(234, 179, 8)',
                                        \App\Models\Order::SLA_STATE_OK => 'rgb(34, 197, 94)',
                                        default => 'transparent',
                                    };
                                    $accentBackground = match ($slaState) {
                                        \App\Models\Order::SLA_STATE_OVERDUE => 'rgb(254, 242, 242)',
                                        \App\Models\Order::SLA_STATE_WARNING => 'rgb(254, 252, 232)',
                                        default => '#fff',
                                    };
                                    $fulfillmentStyle = $order->fulfillment_method === \App\Models\Order::FULFILLMENT_PICKUP
                                        ? 'background: rgb(250, 245, 255); color: rgb(126, 34, 206); border-color: rgba(126, 34, 206, 0.2);'
                                        : 'background: rgb(239, 246, 255); color: rgb(29, 78, 216); border-color: rgba(29, 78, 216, 0.2);';
                                    $customerName = trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Покупатель';
                                    $locationLabel = $order->fulfillment_method === \App\Models\Order::FULFILLMENT_PICKUP
                                        ? ($order->warehouse_name_snapshot ?: 'Пункт самовывоза не указан')
                                        : ($order->city ?: 'Город не указан');
                                    $slaBadgeStyle = match ($slaState) {
                                        \App\Models\Order::SLA_STATE_OVERDUE => 'background: rgb(254, 226, 226); color: rgb(153, 27, 27); border-color: rgb(254, 202, 202);',
                                        \App\Models\Order::SLA_STATE_WARNING => 'background: rgb(254, 249, 195); color: rgb(133, 77, 14); border-color: rgb(254, 240, 138);',
                                        \App\Models\Order::SLA_STATE_OK => 'background: rgb(220, 252, 231); color: rgb(22, 101, 52); border-color: rgb(187, 247, 208);',
                                        default => 'background: rgb(243, 244, 246); color: rgb(75, 85, 99); border-color: rgb(229, 231, 235);',
                                    };
                                @endphp

                                <article
                                    x-data="{ expanded: false }"
                                    @if (! $order->isArchived())
                                        draggable="true"
                                        x-on:dragstart="
                                            draggedOrderId = '{{ $order->id }}';
                                            draggedStatus = '{{ $order->status }}';
                                            $event.dataTransfer.effectAllowed = 'move';
                                            $event.dataTransfer.setData('text/plain', '{{ $order->id }}');
                                            $event.currentTarget.style.opacity = '0.55';
                                        "
                                        x-on:dragend="
                                            $event.currentTarget.style.opacity = '1';
                                            draggedOrderId = null;
                                            draggedStatus = null;
                                            overStatus = null;
                                        "
                                    @endif
                                    style="border: 1px solid rgb(229, 231, 235); border-left: 4px solid {{ $accentBorder }}; border-radius: 0.875rem; background: {{ $accentBackground }}; padding: 0.7rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07); cursor: {{ $order->isArchived() ? 'default' : 'grab' }};"
                                >
                                    <div style="display: grid; gap: 0.55rem;">
                                        <a href="{{ $this->viewOrderUrl($order) }}" style="display: block; color: inherit; text-decoration: none;">
                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.6rem;">
                                                <div style="min-width: 0;">
                                                    <div style="color: rgb(21, 128, 61); font-size: 0.84rem; font-weight: 850; line-height: 1.25; overflow-wrap: anywhere;">{{ $order->order_number }}</div>
                                                    <div style="color: rgb(107, 114, 128); font-size: 0.72rem; margin-top: 0.12rem;">{{ $order->created_at?->format('d.m.Y H:i') }}</div>
                                                </div>
                                                <span style="display: inline-flex; flex: none; white-space: nowrap; border: 1px solid; border-radius: 999px; padding: 0.2rem 0.48rem; font-size: 0.68rem; font-weight: 800; {{ $fulfillmentStyle }}">
                                                    {{ $order->getFulfillmentMethodLabel() }}
                                                </span>
                                            </div>

                                            <div style="display: grid; gap: 0.15rem; margin-top: 0.55rem; color: rgb(55, 65, 81); font-size: 0.8rem; line-height: 1.28;">
                                                <div style="color: rgb(17, 24, 39); font-weight: 800; overflow-wrap: anywhere;">{{ $customerName }}</div>
                                                <div style="color: rgb(75, 85, 99); overflow-wrap: anywhere;">{{ $locationLabel }}</div>
                                            </div>

                                            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 0.65rem; margin-top: 0.55rem;">
                                                <div style="color: rgb(17, 24, 39); font-size: 0.92rem; font-weight: 850; white-space: nowrap;">{{ $this->money($order->total) }}</div>
                                                <span style="display: inline-flex; max-width: 100%; border: 1px solid; border-radius: 999px; padding: 0.22rem 0.5rem; color: rgb(55, 65, 81); font-size: 0.68rem; font-weight: 800; line-height: 1.15; text-align: right; {{ $slaBadgeStyle }}">
                                                    SLA: {{ $order->getSlaTimingLabel() }}
                                                </span>
                                            </div>
                                        </a>

                                        <button
                                            type="button"
                                            x-on:click="expanded = ! expanded"
                                            x-bind:aria-expanded="expanded.toString()"
                                            style="display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; width: 100%; border: 1px solid rgb(229, 231, 235); border-radius: 0.5rem; background: rgba(255, 255, 255, 0.72); padding: 0.34rem 0.55rem; color: rgb(55, 65, 81); font-size: 0.73rem; font-weight: 800; cursor: pointer;"
                                        >
                                            <span x-show="! expanded">▼ Подробнее</span>
                                            <span x-show="expanded">▲ Свернуть</span>
                                        </button>

                                        <div
                                            x-show="expanded"
                                            style="display: grid; gap: 0.4rem; border-top: 1px solid rgb(229, 231, 235); padding-top: 0.55rem; color: rgb(55, 65, 81); font-size: 0.75rem; line-height: 1.35;"
                                        >
                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <span style="color: rgb(107, 114, 128);">Телефон</span>
                                                <span style="color: rgb(17, 24, 39); font-weight: 700; text-align: right; overflow-wrap: anywhere;">{{ $order->phone ?: 'Не указан' }}</span>
                                            </div>

                                            @if ($order->email)
                                                <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                    <span style="color: rgb(107, 114, 128);">Email</span>
                                                    <span style="color: rgb(17, 24, 39); font-weight: 700; text-align: right; overflow-wrap: anywhere;">{{ $order->email }}</span>
                                                </div>
                                            @endif

                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <span style="color: rgb(107, 114, 128);">Оплата</span>
                                                <span style="color: rgb(17, 24, 39); font-weight: 700; text-align: right;">{{ $this->paymentStatusLabel($order) }}</span>
                                            </div>

                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <span style="color: rgb(107, 114, 128);">Получение</span>
                                                <span style="color: rgb(17, 24, 39); font-weight: 700; text-align: right;">{{ $this->fulfillmentStatusLabel($order) }}</span>
                                            </div>

                                            @if ($order->delivery_comment)
                                                <div style="display: grid; gap: 0.15rem;">
                                                    <span style="color: rgb(107, 114, 128);">Комментарий доставки</span>
                                                    <span style="color: rgb(17, 24, 39); font-weight: 700; overflow-wrap: anywhere;">{{ $order->delivery_comment }}</span>
                                                </div>
                                            @endif

                                            @if ($order->comment)
                                                <div style="display: grid; gap: 0.15rem;">
                                                    <span style="color: rgb(107, 114, 128);">Комментарий</span>
                                                    <span style="color: rgb(17, 24, 39); font-weight: 700; overflow-wrap: anywhere;">{{ $order->comment }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="display: flex; flex-wrap: wrap; gap: 0.42rem; margin-top: 0.65rem;">
                                        @foreach ($this->getQuickActions($order) as $targetStatus => $label)
                                            <button
                                                type="button"
                                                wire:click="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                                style="border: 0; border-radius: 0.5rem; background: rgb(22, 101, 52); padding: 0.38rem 0.56rem; color: #fff; font-size: 0.72rem; font-weight: 800; cursor: pointer;"
                                            >
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <div style="display: flex; flex-wrap: wrap; gap: 0.42rem; margin-top: 0.45rem;">
                                        @if ($this->canShowArchiveAction($order))
                                            <button
                                                type="button"
                                                wire:click="confirmArchive({{ $order->id }})"
                                                style="display: inline-flex; align-items: center; gap: 0.35rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.38rem 0.56rem; color: rgb(55, 65, 81); font-size: 0.72rem; font-weight: 800; cursor: pointer;"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 1rem; height: 1rem;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                </svg>
                                                <span>В архив</span>
                                            </button>
                                        @endif

                                        <a
                                            href="{{ $this->editOrderUrl($order) }}"
                                            style="display: inline-flex; align-items: center; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.38rem 0.56rem; color: rgb(55, 65, 81); font-size: 0.72rem; font-weight: 800; text-decoration: none;"
                                        >
                                            Открыть
                                        </a>
                                    </div>
                                </article>
                            @empty
                                <div style="border: 1px dashed rgb(209, 213, 219); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.75); padding: 1rem; color: rgb(107, 114, 128); text-align: center; font-size: 0.875rem;">
                                    Нет заказов
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    @if ($archiveOrderId !== null)
        <div style="position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; background: rgba(15, 23, 42, 0.45); padding: 1rem;">
            <section style="width: min(440px, 100%); border-radius: 1rem; background: #fff; padding: 1.25rem; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);">
                <h2 style="margin: 0; color: rgb(17, 24, 39); font-size: 1.15rem; font-weight: 800;">Архивировать заказ?</h2>

                <p style="margin: 0.75rem 0 0; color: rgb(75, 85, 99); line-height: 1.55;">
                    После архивирования заказ исчезнет с доски заказов,<br>
                    но останется доступным через архив.
                </p>

                <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem;">
                    <button
                        type="button"
                        wire:click="archiveConfirmed"
                        style="border: 0; border-radius: 0.5rem; background: rgb(22, 101, 52); padding: 0.55rem 0.85rem; color: #fff; font-size: 0.875rem; font-weight: 800; cursor: pointer;"
                    >
                        Архивировать
                    </button>

                    <button
                        type="button"
                        wire:click="cancelArchive"
                        style="border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.55rem 0.85rem; color: rgb(55, 65, 81); font-size: 0.875rem; font-weight: 800; cursor: pointer;"
                    >
                        Отмена
                    </button>
                </div>
            </section>
        </div>
    @endif
</x-filament-panels::page>
