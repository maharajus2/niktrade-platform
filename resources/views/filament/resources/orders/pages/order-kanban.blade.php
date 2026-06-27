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

        <div style="height: calc(100vh - 18rem); min-height: 420px; max-height: 780px; overflow-x: auto; overflow-y: hidden; padding-bottom: 0.75rem;">
            <div style="display: flex; gap: 1rem; min-width: max-content; height: 100%; align-items: stretch;">
                @foreach ($this->columns as $status => $column)
                    <section style="width: 320px; min-width: 320px; height: 100%; display: flex; flex-direction: column; border: 1px solid rgb(229, 231, 235); border-radius: 0.875rem; background: rgb(249, 250, 251); padding: 0.75rem;">
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
                                @endphp

                                <article style="border: 1px solid rgb(229, 231, 235); border-left: 4px solid {{ $accentBorder }}; border-radius: 0.875rem; background: {{ $accentBackground }}; padding: 0.875rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07);">
                                    <a href="{{ $this->viewOrderUrl($order) }}" style="display: block; color: inherit; text-decoration: none;">
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                                            <div>
                                                <div style="color: rgb(21, 128, 61); font-size: 0.925rem; font-weight: 800;">{{ $order->order_number }}</div>
                                                <div style="color: rgb(107, 114, 128); font-size: 0.75rem;">{{ $order->created_at?->format('d.m.Y H:i') }}</div>
                                            </div>
                                            <span style="display: inline-flex; white-space: nowrap; border: 1px solid; border-radius: 999px; padding: 0.25rem 0.55rem; font-size: 0.72rem; font-weight: 800; {{ $fulfillmentStyle }}">
                                                {{ $order->getFulfillmentMethodLabel() }}
                                            </span>
                                        </div>

                                        <div style="display: grid; gap: 0.25rem; margin-top: 0.75rem; color: rgb(55, 65, 81); font-size: 0.875rem; line-height: 1.35;">
                                            <div style="color: rgb(17, 24, 39); font-weight: 800;">
                                                {{ trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Покупатель' }}
                                            </div>
                                            <div>{{ $order->phone ?: 'Телефон не указан' }}</div>
                                            <div>
                                                @if ($order->fulfillment_method === \App\Models\Order::FULFILLMENT_PICKUP)
                                                    {{ $order->warehouse_name_snapshot ?: 'Пункт самовывоза не указан' }}
                                                @else
                                                    {{ $order->city ?: 'Город не указан' }}
                                                @endif
                                            </div>
                                        </div>

                                        <dl style="display: grid; gap: 0.5rem; margin: 0.875rem 0 0; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <dt style="color: rgb(107, 114, 128);">Итого</dt>
                                                <dd style="margin: 0; color: rgb(17, 24, 39); font-weight: 800;">{{ $this->money($order->total) }}</dd>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <dt style="color: rgb(107, 114, 128);">Оплата</dt>
                                                <dd style="margin: 0; color: rgb(55, 65, 81); font-weight: 700; text-align: right;">{{ $this->paymentStatusLabel($order) }}</dd>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <dt style="color: rgb(107, 114, 128);">Получение</dt>
                                                <dd style="margin: 0; color: rgb(55, 65, 81); font-weight: 700; text-align: right;">{{ $this->fulfillmentStatusLabel($order) }}</dd>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                                <dt style="color: rgb(107, 114, 128);">SLA</dt>
                                                <dd style="margin: 0; color: rgb(55, 65, 81); font-weight: 700; text-align: right;">{{ $order->getSlaTimingLabel() }}</dd>
                                            </div>
                                        </dl>
                                    </a>

                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.875rem;">
                                        @foreach ($this->getQuickActions($order) as $targetStatus => $label)
                                            <button
                                                type="button"
                                                wire:click="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                                style="border: 0; border-radius: 0.5rem; background: rgb(22, 101, 52); padding: 0.45rem 0.65rem; color: #fff; font-size: 0.75rem; font-weight: 800; cursor: pointer;"
                                            >
                                                {{ $label }}
                                            </button>
                                        @endforeach

                                        <a
                                            href="{{ $this->editOrderUrl($order) }}"
                                            style="display: inline-flex; align-items: center; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; background: #fff; padding: 0.45rem 0.65rem; color: rgb(55, 65, 81); font-size: 0.75rem; font-weight: 800; text-decoration: none;"
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
</x-filament-panels::page>
