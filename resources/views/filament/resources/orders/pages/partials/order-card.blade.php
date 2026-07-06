@php
    use App\Models\Order;

    $slaState = $order->getSlaState();
    $customerName = $this->customerName($order);
    $locationLabel = $this->locationLabel($order);
    $archiveMode = $archiveMode ?? false;
    $compactDate = $archiveMode ? ($order->archived_at ?? $order->created_at) : $order->created_at;
@endphp

<article
    class="orders-work-order is-{{ $slaState }} {{ $archiveMode ? 'is-archive-compact' : '' }}"
    x-data="{
        expanded: false,
        canTouchMoveOrder: @js($this->canMoveOrder($order) && ! $archiveMode),
        touchStartX: 0,
        touchStartY: 0,
        touchLastX: 0,
        touchLastY: 0,
        touchLongPressTimer: null,
        touchLongPressReady: false,
        touchMoved: false,
        touchGestureHandled: false,
        isInteractiveTarget(target) {
            return Boolean(target.closest('button, input, select, textarea, [role=button], .orders-work-order-actions'));
        },
        startTouchOrderGesture(event) {
            if (event.pointerType !== 'touch' || this.isInteractiveTarget(event.target)) {
                return;
            }

            this.touchStartX = event.clientX;
            this.touchStartY = event.clientY;
            this.touchLastX = event.clientX;
            this.touchLastY = event.clientY;
            this.touchMoved = false;
            this.touchGestureHandled = false;
            this.touchLongPressReady = false;
            window.clearTimeout(this.touchLongPressTimer);

            this.touchLongPressTimer = window.setTimeout(() => {
                if (! this.canTouchMoveOrder) {
                    return;
                }

                this.touchLongPressReady = true;
                this.touchGestureHandled = true;
                mobileDragActive = true;
                mobileDragElement = $el;
                draggedOrderId = '{{ $order->id }}';
                draggedStatus = '{{ $order->status }}';
                overStatus = '{{ $order->status }}';
                $el.classList.add('is-touch-dragging');
                $el.setPointerCapture?.(event.pointerId);
            }, 420);
        },
        moveTouchOrderGesture(event) {
            if (event.pointerType !== 'touch') {
                return;
            }

            this.touchLastX = event.clientX;
            this.touchLastY = event.clientY;

            const deltaX = event.clientX - this.touchStartX;
            const deltaY = event.clientY - this.touchStartY;

            if (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8) {
                this.touchMoved = true;
            }

            if (! this.touchLongPressReady && this.touchMoved && Math.abs(deltaY) > Math.abs(deltaX)) {
                window.clearTimeout(this.touchLongPressTimer);
                return;
            }

            if (this.touchLongPressReady) {
                event.preventDefault();
                setMobileOverStatus(event.clientX, event.clientY);
                return;
            }

            if (Math.abs(deltaX) > 14 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25) {
                window.clearTimeout(this.touchLongPressTimer);
            }
        },
        finishTouchOrderGesture(event) {
            if (event.pointerType !== 'touch') {
                return;
            }

            window.clearTimeout(this.touchLongPressTimer);

            if (this.touchLongPressReady) {
                event.preventDefault();
                const targetStatus = mobileStatusFromPoint(this.touchLastX, this.touchLastY) ?? overStatus;
                dropOrder(targetStatus);
                $el.classList.remove('is-touch-dragging');
                this.touchLongPressReady = false;
                return;
            }

            const deltaX = this.touchLastX - this.touchStartX;
            const deltaY = this.touchLastY - this.touchStartY;

            if (Math.abs(deltaX) >= 56 && Math.abs(deltaX) > Math.abs(deltaY) * 1.5) {
                event.preventDefault();
                this.touchGestureHandled = true;
                shiftActiveStatus(deltaX < 0 ? 1 : -1);
            }

            this.touchLongPressReady = false;
            $el.classList.remove('is-touch-dragging');
        },
        cancelTouchOrderGesture() {
            window.clearTimeout(this.touchLongPressTimer);
            this.touchLongPressReady = false;
            mobileDragActive = false;
            if (mobileDragElement === $el) {
                mobileDragElement = null;
            }
            $el.classList.remove('is-touch-dragging');
        },
    }"
    x-on:pointerdown="startTouchOrderGesture($event)"
    x-on:pointermove="moveTouchOrderGesture($event)"
    x-on:pointerup="finishTouchOrderGesture($event)"
    x-on:pointercancel="cancelTouchOrderGesture()"
    x-on:click.capture="if (touchGestureHandled) { $event.preventDefault(); $event.stopPropagation(); touchGestureHandled = false; }"
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
            <small>{{ $compactDate?->format('d.m.Y H:i') }}</small>
        </a>

        @if ($archiveMode)
            <button
                type="button"
                class="orders-work-archive-expand"
                x-on:click="expanded = ! expanded"
                x-bind:aria-expanded="expanded.toString()"
                aria-label="Раскрыть заказ"
            >
                <x-work.icon name="chevron-right" />
            </button>
        @else
            <em class="orders-work-type is-{{ $order->fulfillment_method }}">{{ $order->getFulfillmentMethodLabel() }}</em>
        @endif
    </div>

    <div class="orders-work-archive-body" @if ($archiveMode) x-cloak x-show="expanded" x-transition @endif>
        @if ($archiveMode)
            <em class="orders-work-type is-{{ $order->fulfillment_method }}">{{ $order->getFulfillmentMethodLabel() }}</em>
        @endif

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
            @if (! $archiveMode)
                <button type="button" x-on:click="expanded = ! expanded" x-bind:aria-expanded="expanded.toString()" aria-label="Подробнее">
                    <x-work.icon name="chevron-right" />
                </button>
            @endif
        </div>

        <div class="orders-work-order-details" @if (! $archiveMode) x-cloak x-show="expanded" x-transition @endif>
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
    </div>
</article>
