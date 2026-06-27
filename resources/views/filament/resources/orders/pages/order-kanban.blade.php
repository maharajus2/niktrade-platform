<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::section>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">Тип получения</span>
                    <select
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        wire:model.live="fulfillmentMethod"
                    >
                        @foreach ($this->fulfillmentMethodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">Оплата</span>
                    <select
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        wire:model.live="paymentStatus"
                    >
                        @foreach ($this->paymentStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">SLA</span>
                    <select
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        wire:model.live="sla"
                    >
                        @foreach ($this->slaOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end justify-between gap-3">
                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            wire:model.live="showArchive"
                        >
                        <span>Показывать архив</span>
                    </label>

                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10"
                        wire:click="resetFilters"
                    >
                        Сбросить
                    </button>
                </div>
            </div>
        </x-filament::section>

        <div class="overflow-x-auto pb-2">
            <div class="grid min-w-[1180px] grid-cols-5 gap-4 xl:min-w-0">
                @foreach ($this->columns as $status => $column)
                    <section class="rounded-xl border border-gray-200 bg-gray-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                        <header class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold text-gray-950 dark:text-white">{{ $column['label'] }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $column['orders']->count() }} заказов</p>
                            </div>
                            <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-gray-600 shadow-sm dark:bg-white/10 dark:text-gray-200">
                                {{ $column['orders']->count() }}
                            </span>
                        </header>

                        <div class="space-y-3">
                            @forelse ($column['orders'] as $order)
                                @php
                                    $slaState = $order->getSlaState();
                                    $accent = match ($slaState) {
                                        \App\Models\Order::SLA_STATE_OVERDUE => 'border-l-red-500 bg-red-50/70 dark:bg-red-950/20',
                                        \App\Models\Order::SLA_STATE_WARNING => 'border-l-yellow-400 bg-yellow-50/70 dark:bg-yellow-950/20',
                                        default => 'border-l-transparent bg-white dark:bg-gray-900',
                                    };
                                    $fulfillmentColor = $order->fulfillment_method === \App\Models\Order::FULFILLMENT_PICKUP
                                        ? 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-300'
                                        : 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300';
                                @endphp

                                <article class="rounded-xl border border-l-4 border-gray-200 {{ $accent }} p-3 shadow-sm transition hover:shadow-md dark:border-white/10">
                                    <a class="block" href="{{ $this->viewOrderUrl($order) }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ $order->order_number }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('d.m.Y H:i') }}</div>
                                            </div>
                                            <span class="rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $fulfillmentColor }}">
                                                {{ $order->getFulfillmentMethodLabel() }}
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold text-gray-950 dark:text-white">
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

                                        <dl class="mt-3 grid gap-2 text-xs">
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-gray-500 dark:text-gray-400">Итого</dt>
                                                <dd class="font-semibold text-gray-950 dark:text-white">{{ $this->money($order->total) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-gray-500 dark:text-gray-400">Оплата</dt>
                                                <dd class="text-right font-semibold text-gray-700 dark:text-gray-200">{{ $this->paymentStatusLabel($order) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-gray-500 dark:text-gray-400">Получение</dt>
                                                <dd class="text-right font-semibold text-gray-700 dark:text-gray-200">{{ $this->fulfillmentStatusLabel($order) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-gray-500 dark:text-gray-400">SLA</dt>
                                                <dd class="text-right font-semibold text-gray-700 dark:text-gray-200">{{ $order->getSlaTimingLabel() }}</dd>
                                            </div>
                                        </dl>
                                    </a>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($this->getQuickActions($order) as $targetStatus => $label)
                                            <button
                                                type="button"
                                                class="rounded-lg bg-primary-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-primary-500 disabled:opacity-60"
                                                wire:click="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')"
                                            >
                                                {{ $label }}
                                            </button>
                                        @endforeach

                                        <a
                                            class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                                            href="{{ $this->editOrderUrl($order) }}"
                                        >
                                            Открыть
                                        </a>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-lg border border-dashed border-gray-300 bg-white/70 p-4 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
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
