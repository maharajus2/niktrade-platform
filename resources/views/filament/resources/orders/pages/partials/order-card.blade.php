@php
    use App\Models\Order;

    $slaState = $order->getSlaState();
    $customerName = $this->customerName($order);
    $locationLabel = $this->locationLabel($order);
@endphp

<article
    class="orders-work-order is-{{ $slaState }}"
    x-data="{ expanded: false }"
    @if ($this->canMoveOrder($order))
        draggable="true"
        x-on:dragstart="
            draggedOrderId = '{{ $order->id }}';
            draggedStatus = '{{ $order->status }}';
            $event.dataTransfer.effectAllowed = 'move';
            $event.dataTransfer.setData('text/plain', '{{ $order->id }}');
        "
        x-on:dragend="
            draggedOrderId = null;
            draggedStatus = null;
            overStatus = null;
        "
    @endif
>
    <div class="orders-work-order-main">
        <a href="{{ $this->viewOrderUrl($order) }}">
            <span>{{ $order->order_number }}</span>
            <small>{{ $order->created_at?->format('d.m.Y H:i') }}</small>
        </a>
        <em class="orders-work-type is-{{ $order->fulfillment_method }}">{{ $order->getFulfillmentMethodLabel() }}</em>
    </div>

    <div class="orders-work-order-customer">
        <strong>{{ $customerName }}</strong>
        <span>{{ $locationLabel }}</span>
    </div>

    <div class="orders-work-order-meta">
        <strong>{{ $this->money($order->total) }}</strong>
        <span class="orders-work-sla is-{{ $slaState }}">{{ $order->getSlaLabel() }}</span>
    </div>

    <div class="orders-work-order-icons">
        <span title="Состав"><x-work.icon name="package" /></span>
        <span title="Коммуникации"><x-work.icon name="message" /></span>
        <span title="Срок"><x-work.icon name="calendar" /></span>
        <button type="button" x-on:click="expanded = ! expanded" x-bind:aria-expanded="expanded.toString()" aria-label="Подробнее">
            <x-work.icon name="chevron-right" />
        </button>
    </div>

    <div class="orders-work-order-details" x-cloak x-show="expanded" x-transition>
        <dl>
            <div>
                <dt>Телефон</dt>
                <dd>{{ $order->phone ?: 'Не указан' }}</dd>
            </div>
            @if ($order->email)
                <div>
                    <dt>Email</dt>
                    <dd>{{ $order->email }}</dd>
                </div>
            @endif
            <div>
                <dt>Оплата</dt>
                <dd>{{ $this->paymentStatusLabel($order) }}</dd>
            </div>
            <div>
                <dt>Получение</dt>
                <dd>{{ $this->fulfillmentStatusLabel($order) }}</dd>
            </div>
            @if ($order->delivery_comment)
                <div>
                    <dt>Комментарий доставки</dt>
                    <dd>{{ $order->delivery_comment }}</dd>
                </div>
            @endif
            @if ($order->comment)
                <div>
                    <dt>Комментарий</dt>
                    <dd>{{ $order->comment }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="orders-work-order-actions">
        @foreach ($this->getQuickActions($order) as $targetStatus => $label)
            <button type="button" wire:click="moveToStatus({{ $order->id }}, '{{ $targetStatus }}')" wire:loading.attr="disabled">
                {{ $label }}
            </button>
        @endforeach

        @if ($this->canShowArchiveAction($order))
            <button type="button" wire:click="confirmArchive({{ $order->id }})">В архив</button>
        @endif

        <a href="{{ $this->editOrderUrl($order) }}">Открыть</a>
    </div>
</article>
