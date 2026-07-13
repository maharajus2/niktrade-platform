@php
    use App\Filament\Pages\MyCalendar;
    use App\Filament\Pages\Workplace;
    use App\Filament\Resources\Customers\CustomerResource;
    use App\Filament\Resources\EmployeeScheduleRequests\EmployeeScheduleRequestResource;
    use App\Filament\Resources\Orders\OrderResource;
    use App\Models\Order;
    use App\Support\WeightFormatter;
    use Illuminate\Support\Facades\Storage;

    $order = $this->record->loadMissing(['items.product.images', 'customer', 'warehouse']);
    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->join('')
        : 'N';

    $money = fn ($value): string => number_format((float) $value, 0, ',', ' ') . ' ₽';
    $dateTime = fn ($value): string => $value ? $value->format('d.m.Y H:i') : 'Не указано';
    $shortDate = fn ($value): string => $value ? $value->format('d.m H:i') : '—';

    $customerName = trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Покупатель';
    $deliveryAddress = collect([
        $order->postal_code,
        $order->region,
        $order->city,
        $order->street,
        $order->house ? 'д. '.$order->house : null,
        $order->building ? 'к. '.$order->building : null,
        $order->apartment ? 'кв. '.$order->apartment : null,
    ])->filter()->join(', ');

    $normalizedStatus = match ($order->status) {
        Order::LEGACY_STATUS_PROCESSING => Order::STATUS_ASSEMBLING,
        Order::LEGACY_STATUS_ASSEMBLED, Order::LEGACY_STATUS_HANDED_TO_DELIVERY => Order::STATUS_READY_FOR_DISPATCH,
        Order::LEGACY_STATUS_DELIVERED => Order::STATUS_COMPLETED,
        default => $order->status,
    };

    $statusSteps = [
        Order::STATUS_NEW => ['label' => 'Новый', 'icon' => 'file', 'time' => $order->created_at],
        Order::STATUS_ASSEMBLING => ['label' => 'Сборка', 'icon' => 'package', 'time' => $order->assembling_at],
        Order::STATUS_READY_FOR_DISPATCH => ['label' => 'Отгрузка', 'icon' => 'truck', 'time' => $order->ready_for_dispatch_at ?? $order->assembled_at ?? $order->handed_to_delivery_at],
        Order::STATUS_COMPLETED => ['label' => 'Завершён', 'icon' => 'check-circle', 'time' => $order->delivered_at],
        Order::STATUS_CANCELLED => ['label' => 'Отменён', 'icon' => 'alert', 'time' => $order->cancelled_at],
    ];

    $statusKeys = array_keys($statusSteps);
    $currentStepIndex = array_search($normalizedStatus, $statusKeys, true);
    $currentStepIndex = $currentStepIndex === false ? 0 : $currentStepIndex;

    $timeline = collect([
        ['label' => 'Заказ создан', 'time' => $order->created_at, 'icon' => 'file'],
        ['label' => 'Передан в сборку', 'time' => $order->assembling_at, 'icon' => 'package'],
        ['label' => 'Готов к отгрузке', 'time' => $order->ready_for_dispatch_at ?? $order->assembled_at, 'icon' => 'truck'],
        ['label' => 'Передан в доставку', 'time' => $order->handed_to_delivery_at, 'icon' => 'truck'],
        ['label' => 'Завершён', 'time' => $order->delivered_at, 'icon' => 'check-circle'],
        ['label' => 'Отменён', 'time' => $order->cancelled_at, 'icon' => 'alert'],
        ['label' => 'Архивирован', 'time' => $order->archived_at, 'icon' => 'archive'],
    ])->filter(fn (array $event): bool => filled($event['time']))->values();

    $fulfillmentTitle = $order->fulfillment_method === Order::FULFILLMENT_PICKUP ? 'Самовывоз' : 'Доставка';
    $fulfillmentLocation = $order->fulfillment_method === Order::FULFILLMENT_PICKUP
        ? ($order->warehouse_name_snapshot ?: 'Пункт самовывоза не указан')
        : ($deliveryAddress ?: 'Адрес доставки не указан');

    $mobileMenuGroups = [
        'Главное' => [
            ['label' => 'Рабочее пространство', 'icon' => 'grid', 'url' => Workplace::getUrl()],
            ['label' => 'Заказы', 'icon' => 'package', 'url' => OrderResource::getUrl('index'), 'active' => true],
            ['label' => 'Мой календарь', 'icon' => 'calendar', 'url' => MyCalendar::getUrl()],
        ],
        'Рабочие инструменты' => array_values(array_filter([
            ['label' => 'Задачи', 'icon' => 'check-square', 'url' => \App\Filament\Pages\Tasks::getUrl()],
            ['label' => 'Коммуникации', 'icon' => 'message', 'url' => '#', 'badge' => '2', 'sheet' => 'messages'],
            EmployeeScheduleRequestResource::canAccess()
                ? ['label' => 'Заявки', 'icon' => 'link', 'url' => EmployeeScheduleRequestResource::getUrl('index')]
                : null,
            ['label' => 'Справочники', 'icon' => 'book', 'url' => '#', 'badge' => 'Скоро', 'disabled' => true],
        ])),
        'Продажи' => array_values(array_filter([
            ['label' => 'Заказы', 'icon' => 'package', 'url' => OrderResource::getUrl('index'), 'active' => true],
            CustomerResource::canAccess()
                ? ['label' => 'Покупатели', 'icon' => 'users', 'url' => CustomerResource::getUrl('index')]
                : null,
        ])),
    ];

    $imageUrl = function (?string $path): string {
        return $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    };
@endphp

<x-filament-panels::page>
    @component('layouts.work', [
        'title' => 'Заказ '.$order->order_number,
        'subtitle' => $customerName.' · '.$order->getStatusLabel().' · '.$money($order->total),
        'user' => $user,
        'active' => 'orders',
        'showSidebar' => true,
        'appClass' => 'nik-work-app--orders nik-work-app--order-detail',
    ])
        <div
            class="nik-order-detail"
            x-data="{ activeSheet: null, openSheet(sheet) { this.activeSheet = sheet; document.documentElement.classList.add('nik-work-mobile-sheet-open'); }, closeSheet() { this.activeSheet = null; document.documentElement.classList.remove('nik-work-mobile-sheet-open'); } }"
            x-on:keydown.escape.window="closeSheet()"
        >
            <header class="orders-work-mobile-head nik-order-detail-mobile-head">
                <div>
                    <img src="{{ asset('images/logont.png') }}" alt="Никтрейд" />
                    <h1>Заказ</h1>
                    <p>{{ $order->order_number }}</p>
                </div>
                <div>
                    <a href="{{ OrderResource::getUrl('index') }}" aria-label="К заказам"><x-work.icon name="chevron-left" /></a>
                    <button type="button" aria-label="Уведомления" class="has-badge"><x-work.icon name="bell" /><span>3</span></button>
                    <x-work.user-menu class="orders-work-mobile-user" :user="$user" :initials="$initials" button-class="orders-work-mobile-avatar" :show-chevron="false" />
                </div>
            </header>

            <section class="nik-order-detail-hero">
                <div>
                    <a class="nik-order-detail-back" href="{{ OrderResource::getUrl('index') }}">
                        <x-work.icon name="chevron-left" />
                        <span>К заказам</span>
                    </a>
                    <div class="nik-order-detail-title-row">
                        <div>
                            <span class="nik-order-detail-eyebrow">Карточка заказа</span>
                            <h1>{{ $order->order_number }}</h1>
                            <p>{{ $customerName }} · {{ $dateTime($order->created_at) }}</p>
                        </div>
                        <div class="nik-order-detail-badges">
                            <span class="nik-order-detail-badge is-status">{{ $order->getStatusLabel() }}</span>
                            <span class="nik-order-detail-badge is-{{ $order->getSlaState() }}">{{ $order->getSlaLabel() }}</span>
                            <span class="nik-order-detail-badge">{{ $order->getFulfillmentMethodLabel() }}</span>
                        </div>
                    </div>
                </div>

                <div class="nik-order-detail-actions">
                    <a class="nik-order-detail-action" href="{{ OrderResource::getUrl('edit', ['record' => $order]) }}">
                        <x-work.icon name="file" />
                        <span>Редактировать</span>
                    </a>
                    @if ($order->canBeArchived())
                        <button type="button" class="nik-order-detail-action" wire:click="archiveOrder" wire:confirm="Отправить заказ в архив?">
                            <x-work.icon name="archive" />
                            <span>В архив</span>
                        </button>
                    @elseif ($order->isArchived())
                        <button type="button" class="nik-order-detail-action" wire:click="unarchiveOrder" wire:confirm="Вернуть заказ из архива?">
                            <x-work.icon name="archive" />
                            <span>Вернуть</span>
                        </button>
                    @endif
                </div>
            </section>

            <section class="nik-order-detail-rail" aria-label="Статус заказа">
                @foreach ($statusSteps as $status => $step)
                    @php
                        $stepIndex = array_search($status, $statusKeys, true);
                        $isActive = $normalizedStatus === $status;
                        $isDone = $normalizedStatus !== Order::STATUS_CANCELLED && $stepIndex < $currentStepIndex;
                    @endphp
                    <article class="{{ $isActive ? 'is-active' : '' }} {{ $isDone ? 'is-done' : '' }}">
                        <span><x-work.icon :name="$step['icon']" /></span>
                        <div>
                            <strong>{{ $step['label'] }}</strong>
                            <small>{{ $shortDate($step['time']) }}</small>
                        </div>
                    </article>
                @endforeach
            </section>

            <main class="nik-order-detail-layout">
                <div class="nik-order-detail-main">
                    <section class="nik-order-detail-card">
                        <div class="nik-order-detail-section-head">
                            <div>
                                <span>Состав заказа</span>
                                <h2>Товары</h2>
                            </div>
                            <strong>{{ $order->items->count() }}</strong>
                        </div>

                        <div class="nik-order-detail-items">
                            @forelse ($order->items as $item)
                                @php
                                    $product = $item->product;
                                    $itemImage = $item->product_image_path ?: $product?->images?->first()?->file_path;
                                    $unitPrice = $item->discounted_unit_price ?? $item->unit_price;
                                @endphp
                                <article class="nik-order-detail-item">
                                    <a
                                        class="nik-order-detail-item-image"
                                        @if ($product) href="{{ route('catalog.show', $product) }}" target="_blank" rel="noopener" @endif
                                    >
                                        <img src="{{ $imageUrl($itemImage) }}" alt="{{ $item->product_name }}" loading="lazy">
                                    </a>
                                    <div>
                                        <strong>{{ $item->product_name }}</strong>
                                        <small>{{ $item->product_article ?: 'Артикул не указан' }}</small>
                                        <span>
                                            {{ $item->quantity }} шт · {{ $money($unitPrice) }}
                                            @if ($item->discount_percent)
                                                · скидка {{ rtrim(rtrim((string) $item->discount_percent, '0'), '.') }}%
                                            @endif
                                        </span>
                                    </div>
                                    <strong>{{ $money($item->line_total) }}</strong>
                                </article>
                            @empty
                                <div class="nik-order-detail-empty">В заказе пока нет товаров.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="nik-order-detail-card">
                        <div class="nik-order-detail-section-head">
                            <div>
                                <span>Покупатель и получение</span>
                                <h2>Контакты</h2>
                            </div>
                        </div>

                        <div class="nik-order-detail-facts">
                            <div>
                                <small>Покупатель</small>
                                <strong>{{ $customerName }}</strong>
                            </div>
                            <div>
                                <small>Телефон</small>
                                <strong>{{ $order->phone ?: 'Не указан' }}</strong>
                            </div>
                            <div>
                                <small>Email</small>
                                <strong>{{ $order->email ?: 'Не указан' }}</strong>
                            </div>
                            <div>
                                <small>Тип получения</small>
                                <strong>{{ $fulfillmentTitle }}</strong>
                            </div>
                        </div>

                        <div class="nik-order-detail-address">
                            <span><x-work.icon :name="$order->fulfillment_method === Order::FULFILLMENT_PICKUP ? 'package' : 'truck'" /></span>
                            <div>
                                <small>{{ $fulfillmentTitle }}</small>
                                <strong>{{ $fulfillmentLocation }}</strong>
                                @if ($order->fulfillment_method === Order::FULFILLMENT_PICKUP)
                                    <p>{{ $order->warehouse_address_snapshot ?: 'Адрес пункта не указан' }}</p>
                                    <p>{{ $order->warehouse_phone_snapshot ?: 'Телефон пункта не указан' }} · {{ $order->warehouse_working_hours_snapshot ?: 'Часы работы не указаны' }}</p>
                                @else
                                    <p>
                                        Подъезд {{ $order->entrance ?: '—' }} · Этаж {{ $order->floor ?: '—' }} · Квартира {{ $order->apartment ?: '—' }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if ($order->delivery_comment || $order->comment)
                            <div class="nik-order-detail-comments">
                                @if ($order->delivery_comment)
                                    <div>
                                        <small>Комментарий к доставке</small>
                                        <p>{{ $order->delivery_comment }}</p>
                                    </div>
                                @endif
                                @if ($order->comment)
                                    <div>
                                        <small>Комментарий оператора</small>
                                        <p>{{ $order->comment }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </section>
                </div>

                <aside class="nik-order-detail-side">
                    <section class="nik-order-detail-card nik-order-detail-total-card">
                        <div class="nik-order-detail-section-head">
                            <div>
                                <span>Финансы</span>
                                <h2>Итоги</h2>
                            </div>
                            <strong>{{ $money($order->total) }}</strong>
                        </div>
                        <div class="nik-order-detail-totals">
                            <div><span>Товары</span><strong>{{ $money($order->subtotal) }}</strong></div>
                            <div><span>Скидка</span><strong>{{ $money($order->discount_total) }}</strong></div>
                            <div><span>Доставка</span><strong>{{ $money($order->delivery_total) }}</strong></div>
                            <div class="is-total"><span>К оплате</span><strong>{{ $money($order->total) }}</strong></div>
                            <div><span>Вес</span><strong>{{ WeightFormatter::formatGrams($order->total_weight_grams) }}</strong></div>
                        </div>
                        <div class="nik-order-detail-payment">
                            <span>{{ $order->getPaymentStatusLabel() }}</span>
                            <span>{{ $order->getFulfillmentStatusLabel() }}</span>
                        </div>
                    </section>

                    <section class="nik-order-detail-card">
                        <div class="nik-order-detail-section-head">
                            <div>
                                <span>SLA и обработка</span>
                                <h2>{{ $order->getSlaTimingLabel() }}</h2>
                            </div>
                        </div>
                        <div class="nik-order-detail-facts is-single">
                            <div>
                                <small>Срок обработки</small>
                                <strong>{{ $dateTime($order->getSlaDeadline()) }}</strong>
                            </div>
                            <div>
                                <small>Текущий этап</small>
                                <strong>{{ $order->getStatusLabel() }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="nik-order-detail-card">
                        <div class="nik-order-detail-section-head">
                            <div>
                                <span>История</span>
                                <h2>Таймлайн</h2>
                            </div>
                        </div>
                        <div class="nik-order-detail-timeline">
                            @forelse ($timeline as $event)
                                <div>
                                    <span><x-work.icon :name="$event['icon']" /></span>
                                    <div>
                                        <strong>{{ $event['label'] }}</strong>
                                        <small>{{ $dateTime($event['time']) }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="nik-order-detail-empty">История заказа пока пуста.</div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </main>

            <x-work.mobile-bottom-sheets :menu-groups="$mobileMenuGroups" />
            <x-work.mobile-bottom-nav />
        </div>
    @endcomponent
</x-filament-panels::page>
